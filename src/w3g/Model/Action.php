<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Data\Actions;

class Action extends Model
{
    const PAUSE_GAME         = 0x01;
    const RESUME_GAME        = 0x02;
    const SAVE_GAME          = 0x06;
    const SAVE_GAME_FINISHED = 0x07;

    /* No additional parameters. */
    const UNIT_BUILDING_ABILITY_1 = 0x10;

    /* With target position. */
    const UNIT_BUILDING_ABILITY_2 = 0x11;

    /* With target position and target object ID. */
    const UNIT_BUILDING_ABILITY_3 = 0x12;

    /* Give item to unitor drop item on ground. */
    const GIVE_ITEM = 0x13;

    /* With two target positions and two item IDs. */
    const UNIT_BUILDING_ABILITY_4 = 0x14;

    /* Change selection (unit, building, area). */
    const CHANGE_SELECTION = 0x16;

    const ASSIGN_HOTKEY      = 0x17;
    const SELECT_HOTKEY      = 0x18;
    const SELECT_SUBGROUP    = 0x19;
    const UNKNOWN_1          = 0x21;
    const PRE_SUBSELECT      = 0x1A;
    const SELECT_GROUND_ITEM = 0x1C;
    const CANCEL_HERO_REVIVE = 0x1D;

    /* Remove unit from building queue. */
    const CANCEL_UNIT = 0x1E;

    const UNKNOWN_2                       = 0x1B;
    const CHANGE_ALLY_OPTIONS             = 0x50;
    const TRANSFER_RESOURCES              = 0x51;
    const MAPFILE_TRIGGER_CHAT_COMMAND    = 0x60;
    const ESCAPE_PRESSED                  = 0x61;
    const SCENARIO_TRIGGER                = 0x62;
    const ENTER_CHOOSE_HERO_SKILL_SUBMENU = 0x66;
    const ENTER_CHOOSE_BUILDING_SUBMENU   = 0x67;

    /* Ping. */
    const MINIMAP_SIGNAL = 0x68;

    const CONTINUE_GAME = 0x6A;
    const UNKNOWN_3     = 0x75;
    const W3MMD         = 0x6B;

    /** **/

    /* Shift held down */
    const ABILITY_FLAG_WAYPOINT = 0x001;
    
    const ABILITY_FLAG_APPLY_SUBGROUP = 0x002;
    const ABILITY_FLAG_AREA_EFFECT    = 0x004;
    const ABILITY_FLAG_GROUP_COMMAND  = 0x008;

    /* Move group without formation (formation disabled). */
    const ABILITY_FLAG_GROUP_MOVE_FREELY = 0x010;

    /* Ctrl held down (subgroup command). */
    const ABILITY_FLAG_SUBGROUP_COMMAND = 0x040;

    const ABILITY_FLAG_TOGGLE_AUTOCAST = 0x0100;

    /** **/

    const W3MMD_PREFIX    = "MMD.Dat";
    const W3MMD_INIT      = "init";
    const W3MMD_EVENT     = "event";
    const W3MMD_DEF_EVENT = "defEvent";
    const W3MMD_DEF_VARP  = "defVarP";
    const W3MMD_FLAGP     = "flagP";
    const W3MMD_VARP      = "varP";

    const W3MMD_CHECK = "chk";
    const W3MMD_VALUE = "val";
    
    const W3MMD_FLAG_DRAWER     = 0x01;
    const W3MMD_FLAG_LOSER      = 0x02;
    const W3MMD_FLAG_WINNER     = 0x04;
    const W3MMD_FLAG_LEAVER     = 0x08;
    const W3MMD_FLAG_PRACTICING = 0x10;

    /** **/

    private const STATE_PAUSED   = 0x01;
    private const STATE_UNPAUSED = 0x02;

    private static $state = 0x00;

    public function read (Stream $stream)
    {
        $this->id  = $stream->uint8 ();
        $this->key = $this->keyName ($this->id);

        Logger::debug (
            'Found action: [0x%2X:%s].',
            $this->id, 
            $this->key
        );

        switch ($this->id) {
            default:
                throw new Exception (
                    sprintf (
                        'Encountered unknown action id: [%2X]',
                        $this->id
                    )
                );
            break;

            case self::PAUSE_GAME:
                self::$state |= self::STATE_PAUSED;
                self::$state &= ~self::STATE_UNPAUSED;
            break;

            case self::RESUME_GAME:
                self::$state |= self::STATE_UNPAUSED;
                self::$state &= ~self::STATE_PAUSED;
            break;

            case self::SAVE_GAME:
                $this->saveName = $stream->string ();
            break;

            case self::SAVE_GAME_FINISHED:
                $stream->uint32 ();
            break;

            case self::UNIT_BUILDING_ABILITY_1:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $this->_objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::UNIT_BUILDING_ABILITY_2:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $this->_objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();
            break;

            case self::GIVE_ITEM:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $this->_objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $this->objectId1 = $this->_objectId ($stream);
                $this->objectId2 = $this->_objectId ($stream);
                $this->grounded  = $this->objectId1 === $this->objectId2;

                $this->itemObjectId1 = $this->_objectId ($stream);
                $this->itemObjectId2 = $this->_objectId ($stream);
            break;

            case self::UNIT_BUILDING_ABILITY_3:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $this->_objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $this->objectId1 = $this->_objectId ($stream);
                $this->objectId2 = $this->_objectId ($stream);
                $this->grounded  = $this->objectId1 === $this->objectId2;
            break;

            case self::UNIT_BUILDING_ABILITY_4:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId1      = $this->_objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX1 = $stream->float ();
                $this->locY1 = $stream->float ();

                $this->itemId2 = $this->_objectId ($stream);

                $stream->read (9);

                $this->locX2 = $stream->float ();
                $this->locY2 = $stream->float (); 
            break;

            case self::CHANGE_SELECTION:
                $this->mode       = $stream->int8 ();
                $this->numObjects = $stream->uint16 ();
                
                $this->objects = [];

                for ($i = 0; $i < $this->numObjects; $i++) {
                    $this->objects [] = [
                        $this->_objectId ($stream),
                        $this->_objectId ($stream)
                    ];
                }
            break;

            case self::ASSIGN_HOTKEY:
                $this->group      = $stream->int8 ();
                $this->numObjects = $stream->uint16 ();

                $this->hotkey  = ($this->group + 1) % 10;
                $this->objects = [];

                for ($i = 0; $i < $this->numObjects; $i++) {
                    $this->objects [] = [
                        $this->_objectId ($stream),
                        $this->_objectId ($stream)
                    ];
                }
            break;

            case self::SELECT_HOTKEY:
                $this->group  = $stream->int8 ();
                $this->hotkey = ($this->group + 1) % 10; 
                
                $stream->read (1);
            break;

            case self::SELECT_SUBGROUP:
                /* ItemId and objectId represent the first unit in the newly
                   selected subgroup. */
                $this->itemId    = $this->_objectId ($stream);
                $this->objectId1 = $this->_objectId ($stream);
                $this->objectId2 = $this->_objectId ($stream);
            break;

            case self::UNKNOWN_1:
                 $stream->uint32 ();
                 $stream->uint32 ();
            break;

            case self::PRE_SUBSELECT:
                // $stream->string ();
            break;

            case self::SELECT_GROUND_ITEM:
                $stream->int8 ();

                $this->objectId1 = $this->_objectId ($stream);
                $this->objectId2 = $this->_objectId ($stream);
            break;

            case self::CANCEL_HERO_REVIVE:
                $this->heroId1 = $this->_objectId ($stream);
                $this->heroId2 = $this->_objectId ($stream);
            break;

            case self::CANCEL_UNIT:
                /* 0 = unit currently building
                   1 = first unit in queue,
                   2 = second unit in queue,
                   ...
                   6 = last unit in queue */
                $this->slotNum  = $stream->int8 ();
                $this->objectId = $stream->char (4);
            break;

            case self::UNKNOWN_2:
                $stream->int8 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::CHANGE_ALLY_OPTIONS:
                $this->playerId = $stream->int8 ();

                $flags = $stream->uint32 ();

                $this->allied        = (bool) ($flags & 0x1F);
                $this->sharedVision  = (bool) ($flags & 0x20);
                $this->sharedControl = (bool) ($flags & 0x40);
                $this->sharedVictory = (bool) ($flags & 0x400);
            break;

            case self::TRANSFER_RESOURCES:
                $this->playerId = $stream->int8 ();
                $this->gold     = $stream->uint32 ();
                $this->lumber   = $stream->uint32 ();
            break;

            case self::MAPFILE_TRIGGER_CHAT_COMMAND:
                $stream->uint32 ();
                $stream->uint32 ();

                $stream->string ();
            break;

            case self::ESCAPE_PRESSED:
            break;

            case self::SCENARIO_TRIGGER:
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::ENTER_CHOOSE_HERO_SKILL_SUBMENU:
            break;

            case self::ENTER_CHOOSE_BUILDING_SUBMENU:
            break;

            case self::MINIMAP_SIGNAL:
                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $stream->uint32 ();
            break;

            case self::CONTINUE_GAME:
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::UNKNOWN_3:
                $stream->int8 ();
            break;

            case self::W3MMD:
                $this->intro   = $stream->string ();
                $this->header  = $stream->string ();
                $this->message = $stream->string ();

                $toks = $this->_tokenizeW3MMD ($this->message);

                $this->type = $toks [0];

                if (stripos ($this->header, self::W3MMD_CHECK) === 0) {
                    break;
                }

                switch ($this->type) {
                    case self::W3MMD_INIT:
                        /**
                         * [0] => init
                         * [1] => pid
                         * [2] => {pid}
                         * [3] => {name}
                         */
                        $this->playerId   = (int) $toks [2];
                        $this->playerName = $toks [3]; 
                    break;

                    case self::W3MMD_VARP:
                        /**
                         * [0] => varP
                         * [1] => {pid}
                         * [2] => {varname}
                         * [3] => {operator}
                         * [4] => {value}
                         */
                        $this->playerId = (int) $toks [1];
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
                        $this->playerId = (int) $toks [1];
                        $this->flag     = $toks [2];
                    break;
                }

                $stream->read (4);
            break;
        }
    }

    protected function _objectId (Stream $stream)
    {
        $data = $stream->char (4);

        $code = unpack ('N', $data);
        $code = current ($code);

        if (isset (Actions::$codes [$code])) {
            return Actions::$codes [$code];
        }

        if (!ctype_alnum ($data)) {
            $unknown = '';

            for ($i = 0, $cc = strlen ($data); $i < $cc; $i++) {
                $unknown .= str_pad (bin2hex ($data [$i]), 2, '0', STR_PAD_LEFT) . ' ';
            }

            return trim ($unknown);
        }

        return $data;
    }

    protected function _tokenizeW3MMD ($string)
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