<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class W3MMD extends Model
{
    const W3MMD_PREFIX    = "MMD.Dat";
    const W3MMD_INIT      = "init";
    const W3MMD_EVENT     = "event";
    const W3MMD_DEF_EVENT = "defEvent";
    const W3MMD_DEF_VARP  = "defVarP";
    const W3MMD_FLAGP     = "flagP";
    const W3MMD_VARP      = "varP";

    const W3MMD_INIT_VERSION = "version";
    const W3MMD_INIT_PID     = "pid";

    const W3MMD_CHECK = "chk";
    const W3MMD_VALUE = "val";

    const FLAG_DRAWER     = "drawer";
    const FLAG_LOSER      = "loser";
    const FLAG_WINNER     = "winner";
    const FLAG_LEAVER     = "leaver";
    const FLAG_PRACTICING = "practicing";

    private static $pids      = [];
    private static $events    = [];
    private static $variables = [];

    public function read (Stream $stream, $context = NULL)
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

        $this->intro   = $stream->string ();
        $this->header  = $stream->string ();
        $this->message = $stream->string ();

        $buffer = new Buffer ($this->message);

        $this->type = lcfirst ($buffer->token ());

        switch ($this->type) {
            case self::W3MMD_INIT:
                $this->subtype = $buffer->token ();

                switch ($this->subtype) {
                    case self::W3MMD_INIT_VERSION:
                        /**
                         * [0] => init
                         * [1] => version
                         * [2] => {minimumParserVersion}
                         * [3] => {standardVersion}
                         */
                        $this->version = $buffer->token ();
                    break;

                    case self::W3MMD_INIT_PID:
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

            case self::W3MMD_DEF_EVENT: 
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

            case self::W3MMD_EVENT:
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

            case self::W3MMD_DEF_VARP:  
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

            case self::W3MMD_VARP:
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

                $this->variable = self::get ('variables', $this->varname);
            break;

            case self::W3MMD_FLAGP: 
                /**
                 * [0] => flagP
                 * [1] => {pid}
                 * [2] => {flag}
                 */
                $this->playerId = self::get ('pids', $buffer->token ());
                $this->flag     = $buffer->token ();
            break;
        }

        // 4 unknown bytes.
        $stream->read (4);
    }

    public static function get ($type, $id)
    {
        if (!isset (self::$$type [$id])) {
            throw new Exception (
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