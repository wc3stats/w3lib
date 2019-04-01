<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;

class W3mmd extends Model
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
    
    const W3MMD_FLAG_DRAWER     = 0x01;
    const W3MMD_FLAG_LOSER      = 0x02;
    const W3MMD_FLAG_WINNER     = 0x04;
    const W3MMD_FLAG_LEAVER     = 0x08;
    const W3MMD_FLAG_PRACTICING = 0x10;

    private static $pids = [];

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

        $toks = $this->tokenizeW3MMD ($this->message);
        $this->type = $toks [0];

        switch ($this->type) {
            case self::W3MMD_INIT:
                $this->subtype = $toks [1];

                switch ($this->subtype) {
                    case self::W3MMD_INIT_VERSION:
                        /**
                         * [0] => init
                         * [1] => version
                         * [2] => {version}
                         * [3] => {version}
                         */
                        $this->version = $toks [2];
                    break;

                    case self::W3MMD_INIT_PID:
                        /**
                         * [0] => init
                         * [1] => pid
                         * [2] => {pid}
                         * [3] => {name}
                         */
                        $this->playerId   = (int) $toks [2];
                        $this->playerName = $toks [3];

                        $player = $context->replay->getPlayerByName ($this->playerName);

                        self::$pids [$this->playerId] = $player->id;
                    break;
                }
            break;

            case self::W3MMD_VARP:
                /**
                 * [0] => varP
                 * [1] => {pid}
                 * [2] => {varname}
                 * [3] => {operator}
                 * [4] => {value}
                 */
                $this->playerId = self::$pids [(int) $toks [1]];
                $this->varname  = $toks [2];
                $this->operator = $toks [3];
                $this->value    = trim ($toks [4], ' ",');
            break;

            case self::W3MMD_EVENT:     break;
            case self::W3MMD_DEF_EVENT: break;

            case self::W3MMD_DEF_VARP:  
                /**
                 * [0] => defVarP
                 * [1] => {varname}
                 * [2] => {vartype}
                 * [3] => {goalType}
                 * [4] => {suggestedType}
                 */
                $this->varname = $toks [1];
                $this->vartype = $toks [2];
            break;

            case self::W3MMD_FLAGP: 
                /**
                 * [0] => flagP
                 * [1] => {pid}
                 * [2] => {flag}
                 */
                $this->playerId = self::$pids [(int) $toks [1]];
                $this->flag     = $toks [2];
            break;
        }

        // 4 unknown bytes.
        $stream->read (4);
    }

    private function tokenizeW3MMD ($string)
    {
        $tok  = strtok ($string, " ");
        $toks = [ ];
        
        while ($tok !== FALSE) {
            /* Space has been escaped, _consume. */
            while (substr ($tok, -1) === '\\') {
                $tok = substr ($tok, 0, -1) . ucwords (strtok (" "));
            }

            $toks [] = lcfirst ($tok);
            $tok = strtok (" ");
        }

        return $toks;
    }
}

?>