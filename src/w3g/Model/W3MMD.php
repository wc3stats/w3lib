<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\Library\Exception\RecoverableException;
use w3lib\w3g\Translator\Dota;

use function w3lib\Library\camelCase;
use function w3lib\Library\xxd;

class W3MMD extends Model
{
    const VERSION = 2.1;

    const PREFIX    = "MMD.Dat";
    const INIT      = "init";
    const EVENT     = "event";
    const DEF_EVENT = "defEvent";
    const DEF_VARP  = "defVarP";
    const FLAGP     = "flagP";
    const VARP      = "varP";

    const INIT_VERSION = "version";
    const INIT_PID     = "pid";

    const CHECK = "chk";
    const VALUE = "val";

    const OP_ADD = "+=";
    const OP_SUB = "-=";
    const OP_SET = "=";

    const TYPE_INT  = "int";
    const TYPE_REAL = "real";
    const TYPE_STRING = "string";

    const GOAL_NONE = "none";
    const GOAL_HIGH = "high";
    const GOAL_LOW  = "low";

    const FLAG_DRAWER = "drawer";
    const FLAG_LOSER  = "loser";
    const FLAG_WINNER = "winner";
    const FLAG_LEAVER = "leaver";
    const FLAG_PRACTICING = "practicing";

    const SUGGEST_NONE  = "none";
    const SUGGEST_TRACK = "track";
    const SUGGEST_LEADERBOARD = "leaderboard";

    private static $translators = [
        Dota::class
    ];

    private static $pids      = [];
    private static $events    = [];
    private static $variables = [];

    public function read (Stream &$stream, $context = NULL)
    {
        $this->id = $stream->uint8 ();

        if ($this->id !== Action::W3MMD) {
            throw new Exception (
                sprintf (
                    'Encountered non-w3mmd action id: [%2X]',
                    $this->id
                )
            );
        }

        // xxd ($stream);

        /** **/

        /**
         * Translate the message if it uses a custom w3mmd format.
         */
        foreach (self::$translators as $translator) {
            if ($translator::understands ($stream)) {
                $translator::translate ($stream, $context);
            }
        }

        /** **/

        $this->intro   = $stream->string ();
        $this->header  = $stream->string ();
        $this->message = $stream->string ();

        // 4 unknown bytes.
        $stream->read (4);

        $buffer = new Buffer ($this->message);

        // xxd ($buffer);

        // var_dump ($this->message);
        // xxd ($this->message);

        $this->type = lcfirst ($buffer->token ());

        switch ($this->type) {
            case self::INIT:
                $this->subtype = $buffer->token ();

                switch ($this->subtype) {
                    case self::INIT_VERSION:
                        /**
                         * [0] => init
                         * [1] => version
                         * [2] => {minimumParserVersion}
                         * [3] => {standardVersion}
                         */
                        $this->version = $buffer->token ();
                    break;

                    case self::INIT_PID:
                        /**
                         * [0] => init
                         * [1] => pid
                         * [2] => {pid}
                         * [3] => {name}
                         */
                        $this->playerId   = $buffer->token ();
                        $this->playerName = $buffer->token ();

                        $player = $context->replay->getPlayerByName ($this->playerName);

                        self::$pids [$this->playerId] = $player->id;
                    break;
                }
            break;

            case self::DEF_EVENT:
                /**
                 * [0] => defEvent
                 * [1] => {eventName}
                 * [2] => {numArgs}
                 * [ [3] => {arg1} ]
                 * [ [4] => {arg2} ]
                 * [ [5] => {arg3} ]
                 * [6] => {format}
                 */
                $this->eventName = $this->normalizeKey ($buffer->token ());
                $this->numParams = $buffer->token ();

                $this->params = [];

                for ($i = 0; $i < $this->numParams; $i++) {
                    $this->params [] = $buffer->token ();
                }

                $this->format = $buffer->token ();

                self::$events [$this->eventName] = $this;
            break;

            case self::EVENT:
                /**
                 * [0] => event
                 * [1] => eventName
                 * [ [2] => {arg1} ]
                 * [ [3] => {arg2} ]
                 * [ [4] => {arg3} ]
                 */

                $this->eventName = $this->normalizeKey ($buffer->token ());
                $this->event     = self::get ('events', $this->eventName);
                $this->time      = $context->getTime ();

                $this->args = [];

                for ($i = 0; $i < $this->event->numParams; $i++) {
                    $this->args [] = $buffer->token ();
                }
            break;

            case self::DEF_VARP:
                /**
                 * [0] => defVarP
                 * [1] => {varname}
                 * [2] => {vartype}
                 * [3] => {goalType}
                 * [4] => {suggestedType}
                 */
                $this->varname       = $this->normalizeKey ($buffer->token ());
                $this->varType       = $buffer->token ();
                $this->goalType      = $buffer->token ();
                $this->suggestedType = $buffer->token ();

                self::$variables [$this->varname] = $this;
            break;

            case self::VARP:
                /**
                 * [0] => varP
                 * [1] => {pid}
                 * [2] => {varname}
                 * [3] => {operator}
                 * [4] => {value}
                 */
                $this->playerId = self::get ('pids', $buffer->token ());

                $this->varname  = $this->normalizeKey ($buffer->token ());
                $this->operator = $buffer->token ();
                $this->value    = $this->normalizeValue ($buffer->token ());

                try {
                    $this->variable = self::get ('variables', $this->varname);
                } catch (Exception $e) {
                    // No-op.
                }
            break;

            case self::FLAGP:
                /**
                 * [0] => flagP
                 * [1] => {pid}
                 * [2] => {flag}
                 */
                $this->playerId = self::get ('pids', $buffer->token ());
                $this->flag     = $buffer->token ();

            break;
        }
    }

    public static function get ($type, $id)
    {
        if (!isset (self::$$type [$id])) {
            throw new RecoverableException (
                sprintf (
                    'Encountered undefined [%s]: [%s]',
                    $type,
                    $id
                )
            );
        }

        return self::$$type [$id];
    }

    private function normalizeValue ($s)
    {
        $s = str_replace ([ '\\ ' ], ' ', $s);
        $s = trim ($s, ' ",');

        return $s;
    }

    private function normalizeKey ($s)
    {
        return camelCase (
            $this->normalizeValue ($s)
        );
    }
}

?>
