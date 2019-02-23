<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;

class Game extends Model
{
	const SPEED_SLOW   = 0x00;
	const SPEED_NORMAL = 0x01;
	const SPEED_FAST   = 0x02;

	const VISIBILITY_HIDE_TERRAIN   = 0x00;
	const VISIBILITY_MAP_EXPLORED   = 0x01;
	const VISIBILITY_ALWAYS_VISIBLE = 0x02;
	const VISIBILITY_DEFAULT		= 0x03;

	const OBSERVER_NONE      = 0x00;
	const OBSERVER_ON_DEFEAT = 0x02;
	const OBSERVER_FULL 	 = 0x03;
	const OBSERVER_REFEREE 	 = 0x04;

	const TYPE_LADDER_FFA  = 0x01;
	const TYPE_CUSTOM      = 0x09;
	const TYPE_LOCAL       = 0x0D;
	const TYPE_LADDER_TEAM = 0x20;

	const RACE_HUMAN    = 0x01;
	const RACE_ORC      = 0x02;
	const RACE_NIGHTELF = 0x04;
	const RACE_UNDEAD   = 0x08;
	const RACE_RANDOM   = 0x20;

	const AI_EASY   = 0x01;
	const AI_NORMAL = 0x02;
	const AI_INSANE = 0x04;

	const MODE_TEAM_RACE_SELECTABLE 	= 0x00;
	const MODE_TEAM_NOT_SELECTABLE  	= 0x01;
	const MODE_TEAM_RACE_NOT_SELECTABLE = 0x03;
	const MODE_RACE_FIXED_TO_RANDOM 	= 0x04;
	const MODE_AUTOMATIC_MATCHMAKING 	= 0xCC;

	const CHAT_ALL    	 = 0x00;
	const CHAT_ALLIES 	 = 0x01;
	const CHAT_OBSERVERS = 0x02;
	const CHAT_PAUSED 	 = 0xFE;
	const CHAT_RESUMED 	 = 0xFF;

    public function read (Stream $stream)
    {
        $this->gameName = $stream->string ();

        // 1 null byte.
        $stream->read (1);

        $decoded = '';
        $encoded = $stream->string ();

        for ($i = 0; $i < $encoded; $i++) {
        	if ($i % 8 === 0) {
        		$mask = ord ($encoded [$i]);
        	} else {
        		$decoded = chr (ord ($encoded [$i]) - !($mask & (1 << $i % 8)));
        	}
        }



        /*

	// 4.4 [GameSettings]
        $this->game['speed'] = convert_speed(ord($temp{0}));
        if (ord($temp{1}) & 1) {
            $this->game['visibility'] = convert_visibility(0);
        } elseif (ord($temp{1}) & 2) {
            $this->game['visibility'] = convert_visibility(1);
        } elseif (ord($temp{1}) & 4) {
            $this->game['visibility'] = convert_visibility(2);
        } elseif (ord($temp{1}) & 8) {
            $this->game['visibility'] = convert_visibility(3);
        }
        $this->game['observers'] = convert_observers(((ord($temp{1}) & 16) == true) + 2*((ord($temp{1}) & 32) == true));
        $this->game['teams_together'] = convert_bool(ord($temp{1}) & 64);
        $this->game['lock_teams'] = convert_bool(ord($temp{2}));
        $this->game['full_shared_unit_control'] = convert_bool(ord($temp{3}) & 1);
        $this->game['random_hero'] = convert_bool(ord($temp{3}) & 2);
        $this->game['random_races'] = convert_bool(ord($temp{3}) & 4);
        if (ord($temp{3}) & 64) {
            $this->game['observers'] = convert_observers(4);
        }
        $temp = substr($temp, 13); // 5 unknown bytes + checksum
        // 4.5 [Map&CreatorName]
        $temp = explode(chr(0), $temp);
        $this->game['creator'] = $temp[1];
        $this->game['map'] = $temp[0];
        // 4.6 [PlayerCount]
        $temp = unpack('Vslots', $this->data);
        $this->data = substr($this->data, 4);
        $this->game['slots'] = $temp['slots'];
        // 4.7 [GameType]
        $this->game['type'] = convert_game_type(ord($this->data[0]));
        $this->game['private'] = convert_bool(ord($this->data[1]));
        $this->data = substr($this->data, 8); // 2 bytes are unknown and 4.8 [LanguageID] is useless
        // 4.9 [PlayerList]
        while (ord($this->data{0}) == 0x16) {
            $this->loadplayer();
            $this->data = substr($this->data, 4);
        }
        // 4.10 [GameStartRecord]
        $temp = unpack('Crecord_id/vrecord_length/Cslot_records', $this->data);
        $this->data = substr($this->data, 4);
        $this->game = array_merge($this->game, $temp);
        $slot_records = $temp['slot_records'];
        // 4.11 [SlotRecord]
        for ($i=0; $i<$slot_records; $i++) {
            if ($this->header['major_v'] >= 7) {
                $temp = unpack('Cplayer_id/x1/Cslot_status/Ccomputer/Cteam/Ccolor/Crace/Cai_strength/Chandicap', $this->data);
                $this->data = substr($this->data, 9);
            } elseif ($this->header['major_v'] >= 3) {
                $temp = unpack('Cplayer_id/x1/Cslot_status/Ccomputer/Cteam/Ccolor/Crace/Cai_strength', $this->data);
                $this->data = substr($this->data, 8);
            } else {
                $temp = unpack('Cplayer_id/x1/Cslot_status/Ccomputer/Cteam/Ccolor/Crace', $this->data);
                $this->data = substr($this->data, 7);
            }
            if ($temp['slot_status'] == 2) { // do not add empty slots
                $temp['color'] = convert_color($temp['color']);
                $temp['race'] = convert_race($temp['race']);
                $temp['ai_strength'] = convert_ai($temp['ai_strength']);
                // player ID is always 0 for computer players
                if ($temp['computer'] == 1) {
                    $this->players[] = $temp;
                } else {
                    $this->players[$temp['player_id']] = array_merge($this->players[$temp['player_id']], $temp);
                }
                // Tome of Retraining
                $this->players[$temp['player_id']]['retraining_time'] = 0;
            }
        }
        // 4.12 [RandomSeed]
        $temp = unpack('Vrandom_seed/Cselect_mode/Cstart_spots', $this->data);
        $this->data = substr($this->data, 6);
        $this->game['random_seed'] = $temp['random_seed'];
        $this->game['select_mode'] = convert_select_mode($temp['select_mode']);
        if ($temp['start_spots'] != 0xCC) { // tournament replays from battle.net website don't have this info
            $this->game['start_spots'] = $temp['start_spots'];
        }

        */

//      1 |   4 byte | Unknown (0x00000110 - another record id?)
//  2 | variable | PlayerRecord (see 4.1)
//  3 | variable | GameName (null terminated string) (see 4.2)
//  4 |   1 byte | Nullbyte
//  5 | variable | Encoded String (null terminated) (see 4.3)
//    |          |  - GameSettings (see 4.4)
//    |          |  - Map&CreatorName (see 4.5)
//  6 |   4 byte | PlayerCount (see 4.6)
//  7 |   4 byte | GameType (see 4.7)
//  8 |   4 byte | LanguageID (see 4.8)
//  9 | variable | PlayerList (see 4.9)
// 10 | variable | GameStartRecord (see 4.11)
    }
}

?>