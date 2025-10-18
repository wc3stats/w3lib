<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Model\Action;

class W3MMDV2 extends Model
{
   public static $keys = [];
   public static $game = [];
   public static $players = [];

   public function read (Stream &$stream)
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
      $this->message = utf8_encode ($stream->readTo (Stream::NUL));

      $parsed = self::parse ($this->message);

      switch ($parsed [0]) {
         case 'meta':
            if (isset ($parsed [1])) {
               switch ($parsed [1]) {
                  case 'player':
                     self::$players [$parsed ['store'] ['id']] = array_merge (
                        $parsed ['store'],
                        [
                           'frame' => null,
                           'frames' => []
                        ]
                     );
                  break;

                  case 'keys':
                     self::$keys = $parsed ['store'];
                  break;
               }
            }
         break;

         case 'game':
            self::$game = array_merge (self::$game, $parsed ['store']);
         break;

         case 'player':
            $frame = array_merge (
               self::$players [$parsed [1]] ['frame'] ?? [],
               
               [
                  'round' => self::$game ['round'],
                  'turn'  => self::$game ['turn']
               ],

               $parsed ['store']
            );

            self::$players [$parsed [1]] ['frame'] =  $frame;
            self::$players [$parsed [1]] ['frames'] [] = $frame;
         break;
      }

      $stream->read (1);

      // 4 unknown bytes.
      $stream->read (4);
   }

   private static function parse ($line) 
   {
      $line = trim ($line);

      $parsed = [];

      if (preg_match ('/^(?:\w+\s)+/', $line, $preamble)) {
         $parsed = explode (' ', trim ($preamble [0]));
      }

      preg_match_all ('/([a-z_]+)=("([^"\\\\]*(\\\\.[^"\\\\]*)*)"|[^\s]+)/i', $line, $matches);

      $store = [];

      foreach ($matches [0] as $index => $match) {
         $value = ($matches [2] [$index]);
         $value = trim ($value, ' "');

         if (is_numeric ($value)) {
            if (ctype_digit ($value)) {
               $value = (int) $value;
            } else {
               $value = (float) $value;
            }
         }

         $key = $matches [1] [$index];

         if (isset (self::$keys [$key])) {
            $key = self::$keys [$key];
         }

         $store [$key] = $value;
      }

      $parsed ['store'] = $store;

      return $parsed;
   }
}