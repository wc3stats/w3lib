<?php

namespace w3lib\w3g;

use Exception;
use w3lib\Library\Parser as Model;

class Parser extends Stream
{
    public function parse ()
    {
        $header = new Model ([
            'intro'             => Model::string (),
            'headerSize'        => Model::uint32 (),
            'compressedSize'    => Model::uint32 (),
            'headerVersion'     => Model::uint32 (),
            'uncompressedSize'  => Model::uint32 (),
            'numBlocks'         => Model::uint32 (),
            'identification'    => Model::char   (4),
            'majorVersion'      => Model::uint32 (),
            'buildVersion'      => Model::uint16 (),
            'flags'             => Model::uint16 (),
            'length'            => Model::uint32 (),
            'checksum'          => Model::uint32 ()
        ]);
    }

    // const MISC_MAX_DATABLOCK = 1500;
    // const MISC_APM_DELAY     = 200;

    // const ACTION_ID_TYPE_STRING  = 0x01;
    // const ACTION_ID_TYPE_NUMERIC = 0x02;

    // const FILE_INTRO            = "Warcraft III recorded game";

    // const W3MMD_PREFIX          = "MMD.Dat";
    // const W3MMD_INIT            = "Init";
    // const W3MMD_EVENT           = "Event";
    // const W3MMD_DEF_EVENT       = "DefEvent";
    // const W3MMD_DEF_VARP        = "DefVarP";
    // const W3MMD_FLAGP           = "FlagP";
    // const W3MMD_VARP            = "VarP";

    // const W3MMD_FLAG_DRAWER     = 0x01;
    // const W3MMD_FLAG_LOSER      = 0x02;
    // const W3MMD_FLAG_WINNER     = 0x04;
    // const W3MMD_FLAG_LEAVER     = 0x08;
    // const W3MMD_FLAG_PRACTICING = 0x10;

    // const STATE_DESELECT        = 0x01;
    // const STATE_SUBGROUP        = 0x02;
    // const STATE_BASIC_ACTION    = 0x04;

    // const EVENT_CREATE_OBJECT   = 'Create';
    // const EVENT_CANCEL_OBJECT   = 'Cancel';

    // const LEAVE_VOLUNTARILY     = 'Left';
    // const LEAVE_DISCONNECTED    = 'Disconnected';

    // const ACTION_MACRO          = 0x01;
    // const ACTION_MICRO          = 0x02;
    // const ACTION_GENERAL        = 0x04;

    // private $_header = [
    //     'intro'             => Types::string (),
    //     'headerSize'        => Types::uint32 (),
    //     'compressedSize'    => Types::uint32 (),
    //     'headerVersion'     => Types::uint32 (),
    //     'uncompressedSize'  => Types::uint32 (),
    //     'numBlocks'         => Types::uint32 (),
    //     'identification'    => Types::char (4),
    //     'majorVersion'      => Types::uint32 (),
    //     'buildVersion'      => Types::uint16 (),
    //     'flags'             => Types::uint16 (),
    //     'length'            => Types::uint32 (),
    //     'checksum'          => Types::uint32 ()
    // ];

    // private $_block = [
    //     'compressedSize'   => Types::uint16 (),
    //     'uncompressedSize' => Types::uint16 (),
    //     'checksum'         => Types::uint32 (),
    //     'unknown'          => Types::char (2)
    // ];

    // private $_player = [
    //     'recordId'   => Types::uint8 (),
    //     'playerId'   => Types::uint8 (),
    //     'playerName' => Types::string (),
    //     'type'       => Types::uint8 (),
    //     'flags'      => Types::uint8 ()
    // ];


    // public function parse ()
    // {
    //     debug ('Parsing replay header');


 
    //     // $header = $this->unpack (Header::class);

    //     var_dump ($header);
    //     die ();
        
    //     // $header = $this->unpack ($this->_header);

    //     // if (strpos ($header ['intro'], self::FILE_INTRO) !== 0) {
    //     //     throw new Exception (
    //     //         sprintf (
    //     //             'Unrecognized replay file intro: [%s]',
    //     //             $header ['intro']
    //     //         )
    //     //     );
    //     // }

    //     // debug ('Parsing replay blocks');

    //     // for ($i = 0; $i < $header ['numBlocks']; $i++) {
    //     //     $blockHeader = $this->unpack ($this->_block);

    //     //     $block = $this->read ($blockHeader ['compressedSize']);            
            
    //     //     // Last bit in the first byte needs to be set.
    //     //     $block [0] = chr (ord ($block [0]) | 1);

    //     //     if (! ($block = gzinflate ($block))) {
    //     //         throw new Exception (
    //     //             sprintf (
    //     //                 'Failed to gzinflate replay block #%d',
    //     //                 $i
    //     //             )
    //     //         );
    //     //     }

    //     //     $block = new Binary ($block);

    //     //     if ($i === 0) {
    //     //         $block->read (4);
                
    //     //         $owner = $block->unpack ($this->_player);
            
    //     //         var_dump ($owner);
    //     //     }


    //     //     die ();
    //     // }
    // }
}

?>