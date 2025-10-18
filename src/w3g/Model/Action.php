<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Context;
use w3lib\w3g\Lang;

use function w3lib\Library\xxd;

class Action extends Model
{
    const UNKNOWN_20         = 0x00;

    const PAUSE_GAME         = 0x01;
    const RESUME_GAME        = 0x02;
    const SAVE_GAME          = 0x06;
    const SAVE_GAME_FINISHED = 0x07;

    // No additional parameters.
    const UNIT_BUILDING_ABILITY_1 = 0x10;

    // With target position.
    const UNIT_BUILDING_ABILITY_2 = 0x11;

    // With target position and target object ID.
    const UNIT_BUILDING_ABILITY_3 = 0x12;

    // Give item to unitor drop item on ground.
    const GIVE_ITEM = 0x13;

    // With two target positions and two item IDs.
    const UNIT_BUILDING_ABILITY_4 = 0x14;

    // Change selection (unit, building, area).
    const CHANGE_SELECTION = 0x16;

    const ASSIGN_HOTKEY      = 0x17;
    const SELECT_HOTKEY      = 0x18;
    const SELECT_SUBGROUP    = 0x19;
    const UNKNOWN_1          = 0x21;
    const UNKNOWN_14         = 0x30;
    const UNKNOWN_9          = 0x31;
    const UNKNOWN_16         = 0x32;
    const UNKNOWN_10         = 0x33;
    const UNKNOWN_21         = 0x34;
    const UNKNOWN_15         = 0x35;
    const UNKNOWN_22         = 0x36;
    const PRE_SUBSELECT      = 0x1A;
    const SELECT_GROUND_ITEM = 0x1C;
    const CANCEL_HERO_REVIVE = 0x1D;

    // Remove unit from building queue.
    const CANCEL_UNIT = 0x1E;

    const UNKNOWN_2                       = 0x1B;
    const UNKNOWN_11                      = 0x41;
    const CHANGE_ALLY_OPTIONS             = 0x50;
    const TRANSFER_RESOURCES              = 0x51;
    const MAPFILE_TRIGGER_CHAT_COMMAND    = 0x60;
    const ESCAPE_PRESSED                  = 0x61;
    const SCENARIO_TRIGGER                = 0x62;
    const UNKNOWN_12                      = 0x65;
    const ENTER_CHOOSE_HERO_SKILL_SUBMENU = 0x66;
    const ENTER_CHOOSE_BUILDING_SUBMENU   = 0x67;


    // Ping.
    const MINIMAP_SIGNAL = 0x68;

    const CONTINUE_GAME = 0x6A;
    const UNKNOWN_8     = 0x70;
    const UNKNOWN_23    = 0x73;
    const UNKNOWN_3     = 0x75;
    const UNKNOWN_4     = 0x7B;
    const UNKNOWN_5     = 0x69;
    const UNKNOWN_18    = 0x71;
    const UNKNOWN_13    = 0x72;
    const UNKNOWN_17    = 0x74;
    const UNKNOWN_6     = 0x76;
    const UNKNOWN_7     = 0x77;
    const W3MMD         = 0x6B;
    const UNKNOWN_19    = 0x6D;
    const UNKNOWN_24    = 0x6E;
    const UNKNOWN_25    = 0x78;

    // Patch 1.33
    const UNKNOWN_26    = 0x7A;
    const UNKNOWN_27    = 0x47;
    const UNKNOWN_28    = 0x48;
    const UNKNOWN_29    = 0x45;

    // Patch 2.0

    const UNKNOWN_30    = 0x44;
    const UNKNOWN_31    = 0x46;
    const UNKNOWN_32    = 0x5F;
    const UNKNOWN_33    = 0x3D;

    /** **/

    // Shift held down.
    const ABILITY_FLAG_WAYPOINT = 0x001;

    const ABILITY_FLAG_APPLY_SUBGROUP = 0x002;
    const ABILITY_FLAG_AREA_EFFECT    = 0x004;
    const ABILITY_FLAG_GROUP_COMMAND  = 0x008;

    // Move group without formation (formation disabled).
    const ABILITY_FLAG_GROUP_MOVE_FREELY = 0x010;

    // Ctrl held down (subgroup command).
    const ABILITY_FLAG_SUBGROUP_COMMAND = 0x040;

    const ABILITY_FLAG_TOGGLE_AUTOCAST = 0x0100;

    /** **/

    private const STATE_PAUSED   = 0x01;
    private const STATE_UNPAUSED = 0x02;

    private static $state = 0x00;

    public function read (Stream &$stream)
    {
        $this->id   = $stream->uint8 ();
        $this->key  = $this->keyName ($this->id);
        $this->time = Context::getTime ();

        Logger::debug (
            'Found action: [0x%2X:%s].',
            $this->id,
            $this->key
        );

        switch ($this->id) {
            default:
                \w3lib\Library\xxd ($stream);

                throw new Exception (
                    sprintf (
                        'Encountered unknown action id: [%2X]',
                        $this->id
                    )
                );
            break;

            case self::UNKNOWN_20:
                // 00 [00 00 08 00 0d 00]
                // 00 [00 00 07 00 0d 00]
                // 00 [00 00 0f 00 0d 00]
                // 00 [00 00 19 00 0d 00]
                // 00 [00 00 29 00 0d 00]
                // ...
                $stream->read (6);
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
                $this->itemId       = Lang::objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::UNIT_BUILDING_ABILITY_2:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = Lang::objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();
            break;

            case self::GIVE_ITEM:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = Lang::objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $this->objectId1 = Lang::objectId ($stream);
                $this->objectId2 = Lang::objectId ($stream);
                $this->grounded  = $this->objectId1 === $this->objectId2;

                $this->itemObjectId1 = Lang::objectId ($stream);
                $this->itemObjectId2 = Lang::objectId ($stream);
            break;

            case self::UNIT_BUILDING_ABILITY_3:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = Lang::objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $this->objectId1 = Lang::objectId ($stream);
                $this->objectId2 = Lang::objectId ($stream);
                $this->grounded  = $this->objectId1 === $this->objectId2;
            break;

            case self::UNIT_BUILDING_ABILITY_4:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId1      = Lang::objectId ($stream);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX1 = $stream->float ();
                $this->locY1 = $stream->float ();

                $this->itemId2 = Lang::objectId ($stream);

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
                        Lang::objectId ($stream),
                        Lang::objectId ($stream)
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
                        Lang::objectId ($stream),
                        Lang::objectId ($stream)
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
                $this->itemId    = Lang::objectId ($stream);
                $this->objectId1 = Lang::objectId ($stream);
                $this->objectId2 = Lang::objectId ($stream);
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

                $this->objectId1 = Lang::objectId ($stream);
                $this->objectId2 = Lang::objectId ($stream);
            break;

            case self::CANCEL_HERO_REVIVE:
                $this->heroId1 = Lang::objectId ($stream);
                $this->heroId2 = Lang::objectId ($stream);
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

            case self::UNKNOWN_11:
                // 41 [72 6b 57 68] ArkWh
                // Always followed by 0x10
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

            case self::UNKNOWN_4:
                // 7b [51 33 00 00] [51 33 00 00]
                $stream->int8 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::UNKNOWN_5:
                // 69 [0b 33 00 00] [b5 33 00 00] [0a 33 00 00] [b4 33 00 00]
                $stream->int8 ();
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::UNKNOWN_6:
                // 76 [2c 00] [00 00 00 00] [00 00 00 01]
                $stream->uint16 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::UNKNOWN_7:
            case self::UNKNOWN_8:
            case self::UNKNOWN_9:
            case self::UNKNOWN_10:
            case self::UNKNOWN_12:
            case self::UNKNOWN_13:
            case self::UNKNOWN_14:
            case self::UNKNOWN_15:
            case self::UNKNOWN_16:
            case self::UNKNOWN_17:
            case self::UNKNOWN_21:
            case self::UNKNOWN_22:
            case self::UNKNOWN_24:
                // 30 [30 41] [2d 02 0d 00] 00A-...
                // 31 [39 41] [08 02 0d 00] 10A....
                // 32 [30 41] [9a 00 0d 00] 20A....
                // 33 [30 41] [15 02 0d 00] 30A....
                // 34 [30 41] [01 01 0d 00] 40A....
                // 35 [30 41] [97 00 0d 00] 50A....
                // 36 [30 41] [40 01 0d 00] 60A@...
                // 77 [4f 41] [9f 00 0d 00] wOA....
                // 65 [48 41] [72 63 4f 41] eHArcOA

                // 70 [75 41] [35 30 30 68] puA500h
                // Always followed by 0x10

                // 74 [48 41] [2e 01 0d 00] HA.....
                // Always followed by 0x11

                // 72 [68 41] [38 00 0d 00] rhA8...
                // 6E [61 46] [2e 02 0d 00] .aF....
                $stream->uint16 ();
                $stream->uint32 ();
            break;

            case self::UNKNOWN_18:
            case self::UNKNOWN_23:
                // 71 [1a 00] [75 62 55 41] [34 30 30 68] ..ubUA400h
                // 73 [00 00] [75 62 55 41] [65 73 48 68] ..ubUAesHh

                $stream->uint16 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case self::W3MMD:
                // W3mmd stores as a chain of variables which are not necessarily
                // associated to the current action's playerId. Prepend the ID
                // so we can unpackAll the chain.

                $stream->prepend (self::W3MMD, 'c');

                if (@W3MMD::$meta ['version'] === 2) {
                    // Stores on V2 state.
                    W3MMDV2::unpack ($stream);
                } else {
                    $this->w3mmd = W3MMD::unpack ($stream);
                }
            break;

            case self::UNKNOWN_19:
                // 6D 67 42 68 [.gBh]
                // Always same
                $stream->read (3);
            break;

            case self::UNKNOWN_25:
                $stream->read (20);
            break;

            case self::UNKNOWN_26:
                $stream->read (20);
            break;

            case self::UNKNOWN_27:
                $stream->read (21);
            break;

            case self::UNKNOWN_28:
               $stream->read (6);
            break;

            case self::UNKNOWN_30:
            case self::UNKNOWN_31:
               $stream->read (6);
            break;

            case self::UNKNOWN_32:
               $stream->read (10);
            break;

            case self::UNKNOWN_33:
               $stream->read (13);
            break;
        }

        if (Logger::isDebug ()) {
            echo json_encode ($this, JSON_PRETTY_PRINT);
            echo PHP_EOL;
        }
    }
}

?>