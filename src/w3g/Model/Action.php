<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;

class Action extends Model
{
    const STATE_SELECT   = 0x01;
    const STATE_DESELECT = 0x02;
    const STATE_PAUSED   = 0x04;
    const STATE_UNPAUSED = 0x08;

    private static $_state = 0x00;

    private $_codes = [
        'Pause Game'                                                           => 0x01,
        'Resume Game'                                                          => 0x02,
        'Save Game Finished'                                                   => 0x07,
        'Save Game'                                                            => 0x06,
        'Unit / Building Ability (no additional parameters)'                   => 0x10,
        'Unit / Building Ability (with target position)'                       => 0x11,
        'Unit / Building Ability (with target position and target object ID)'  => 0x12,
        'Give Item to Unit / Drop Item on Ground'                              => 0x13,
        'Unit / Building Ability (with two target positions and two item IDs)' => 0x14,
        'Change Selection (Unit, Building, Area)'                              => 0x16,
        'Assign Group Hotkey'                                                  => 0x17,
        'Select Group Hotkey'                                                  => 0x18,
        'Select Subgroup'                                                      => 0x19, 
        'Pre Sub-Selection'                                                    => 0x1A,
        'Select Ground Item'                                                   => 0x1C,
        'Cancel Hero Revival'                                                  => 0x1D,
        'Remove Unit From Building Queue'                                      => 0x1E,
        '(1) Unknown'                                                          => 0x1B,
        '(2) Unknown'                                                          => 0x21,
        'Change Ally Options'                                                  => 0x50,
        'Transfer Resources'                                                   => 0x51,
        'MapFile Trigger Chat Command'                                         => 0x60,
        'Escape Pressed'                                                       => 0x61,
        'Scenario Trigger'                                                     => 0x62,
        'Enter Choose Hero Skill Submenu'                                      => 0x66,
        'Enter Choose Building Submenu'                                        => 0x67,
        'Minimap Signal (Ping)'                                                => 0x68,
        'Continue Game'                                                        => 0x6A,
        '(3) Unknown'                                                          => 0x75, 
        'W3MMD'                                                                => 0x6B
    ];

    public function read (Stream $stream)
    {
        $this->id = $stream->byte ();

        switch ($this->id) {
            default:
                $stream->prepend ($this->id);

                throw new Exception (
                    sprintf (
                        'Encountered unknown action id: [%2X]',
                        $this->id
                    )
                );
            break;

            case $this->_codes ['Pause Game']:
                self::$_state |= self::STATE_PAUSED;
                self::$_state &= ~self::STATE_UNPAUSED;
            break;

            case $this->_codes ['Resume Game']:
                self::$_state |= self::STATE_UNPAUSED;
                self::$_state &= ~self::STATE_PAUSED;
            break;

            case $this->_codes ['Save Game']:
                $this->saveName = $stream->string ();
            break;

            case $this->_codes ['Save Game Finished']:
                $stream->uint32 ();
            break;

            case $this->_codes ['Unit / Building Ability (no additional parameters)']:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $stream->char (4);

                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case $this->_codes ['Unit / Building Ability (with target position)']:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $stream->char (4);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();
            break;

            case $this->_codes ['Unit / Building Ability (with target position and target object ID)']:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $stream->char (4);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $this->objectId1 = $stream->char (4);
                $this->objectId2 = $stream->char (4);
                $this->grounded  = $this->objectId1 === $this->objectId2;
            break;

            case $this->_codes ['Give Item to Unit / Drop Item on Ground']:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId       = $stream->char (4);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $this->objectId1 = $stream->char (4);
                $this->objectId2 = $stream->char (4);
                $this->grounded  = $this->objectId1 === $this->objectId2;

                $this->itemObjectId1 = $stream->char (4);
                $this->itemObjectId2 = $stream->char (4);
            break;

            case $this->_codes ['Unit / Building Ability (with two target positions and two item IDs)']:
                $this->abilityFlags = $stream->uint16 ();
                $this->itemId1      = $stream->char (4);

                $stream->uint32 ();
                $stream->uint32 ();

                $this->locX1 = $stream->float ();
                $this->locY1 = $stream->float ();

                $this->itemId2 = $stream->char (4);

                $stream->read (9);

                $this->locX2 = $stream->float ();
                $this->locY2 = $stream->float (); 
            break;

            case $this->_codes ['Change Selection (Unit, Building, Area)']:
                $this->mode       = $stream->byte ();
                $this->numObjects = $stream->uint16 ();

                switch ($this->mode) {
                    case self::STATE_SELECT:
                        $this->_state |= self::STATE_SELECT;
                        $this->_state &= ~self::STATE_DESELECT;
                    break;

                    case self::STATE_DESELECT:
                        $this->_state |= self::STATE_DESELECT;
                        $this->_state &= ~self::STATE_SELECT;
                    break;
                }

                $this->objects = [];

                for ($i = 0; $i < $this->numObjects; $i++) {
                    $this->objects [] = [
                        $stream->char (4),
                        $stream->char (4)
                    ];
                }
            break;

            case $this->_codes ['Assign Group Hotkey']:
                $this->group      = $stream->byte ();
                $this->numObjects = $stream->uint16 ();

                $this->hotkey  = ($this->group + 1) % 10;
                $this->objects = [];

                for ($i = 0; $i < $this->numObjects; $i++) {
                    $this->objects [] = [
                        $stream->char (4),
                        $stream->char (4)
                    ];
                }
            break;

            case $this->_codes ['Select Group Hotkey']:
                $this->group  = $stream->byte ();
                $this->hotkey = ($this->group + 1) % 10; 
                
                $stream->read (1);
            break;

            case $this->_codes ['Select Subgroup']:
                /* ItemId and objecvtId represent the first unit in the newly
                   selected subgroup. */
                $this->itemId    = $stream->char (4);
                $this->objectId1 = $stream->char (4);
                $this->objectId2 = $stream->char (4);
            break;

            case $this->_codes ['Pre Sub-Selection']:
                $stream->string ();
            break;

            case $this->_codes ['Select Ground Item']:
                $stream->byte ();

                $this->objectId1 = $stream->char (4);
                $this->objectId2 = $stream->char (4);
            break;

            case $this->_codes ['Cancel Hero Revival']:
                $this->heroId1 = $stream->char (4);
                $this->heroId2 = $stream->char (4);
            break;

            case $this->_codes ['Remove Unit From Building Queue']:
                /* 0 = unit currently building
                   1 = first unit in queue,
                   2 = second unit in queue,
                   ...
                   6 = last unit in queue */
                $this->slotNum  = $stream->byte ();
                $this->objectId = $stream->char (4);
            break;

            case $this->_codes ['(1) Unknown']:
                $stream->byte ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case $this->_codes ['(2) Unknown']:
                 $stream->uint32 ();
                 $stream->uint32 ();
            break;

            case $this->_codes ['Change Ally Options']:
                $this->playerId = $stream->byte ();

                $flags = $stream->uint32 ();

                $this->allied        = (bool) ($flags & 0x1F);
                $this->sharedVision  = (bool) ($flags & 0x20);
                $this->sharedControl = (bool) ($flags & 0x40);
                $this->sharedVictory = (bool) ($flags & 0x400);
            break;

            case $this->_codes ['Transfer Resources']:
                $this->playerId = $stream->byte ();
                $this->gold     = $stream->uint32 ();
                $this->lumber   = $stream->uint32 ();
            break;

            case $this->_codes ['MapFile Trigger Chat Command']:
                $stream->uint32 ();
                $stream->uint32 ();

                $stream->string ();
            break;

            case $this->_codes ['Escape Pressed']:
            break;

            case $this->_codes ['Scenario Trigger']:
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case $this->_codes ['Enter Choose Hero Skill Submenu']:
            break;

            case $this->_codes ['Enter Choose Building Submenu']:
            break;

            case $this->_codes ['Minimap Signal (Ping)']:
                $this->locX = $stream->float ();
                $this->locY = $stream->float ();

                $stream->uint32 ();
            break;

            case $this->_codes ['Continue Game']:
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case $this->_codes ['(3) Unknown']:
                $stream->byte ();
            break;

            case $this->_codes ['W3MMD']:
                $this->intro   = $stream->string ();
                $this->header  = $stream->string ();
                $this->message = $stream->string ();

                $stream->read (4);
            break;
        }
    }
}

?>