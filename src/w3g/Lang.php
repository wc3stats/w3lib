<?php

namespace w3lib\w3g;

use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Data\Actions;
use w3lib\w3g\Model\Game;
use w3lib\w3g\Model\Player;

class Lang
{
    // 4.1 [PlayerRecord] - Record ID
    const HOST    = 0x00;
    const PLAYER  = 0x16;

    // 4.1 [PlayerRecord] - Platform
    const CUSTOM  = 0x01;
    const NETEASE = 0x02;
    const LADDER  = 0x08;

    // 4.1 [PlayerRecord] - Race
    const HUMAN    = 0x01;
    const ORC      = 0x02;
    const NIGHTELF = 0x04;
    const UNDEAD   = 0x08;
    const DAEMON   = 0x10;
    const RANDOM   = 0x20;
    const FIXED    = 0x40;

    // 4.4 [GameSettings] - Speed
    const SPEED_SLOW   = 0x00;
    const SPEED_NORMAL = 0x01;
    const SPEED_FAST   = 0x02;

    // 4.4 [GameSettings] - Visibility
    const VISIBILITY_HIDE_TERRAIN   = 0x00;
    const VISIBILITY_MAP_EXPLORED   = 0x01;
    const VISIBILITY_ALWAYS_VISIBLE = 0x02;
    const VISIBILITY_DEFAULT        = 0x03;

    // 4.4 [GameSettings] - Observer
    const OBSERVER_NONE      = 0x00;
    const OBSERVER_ON_DEFEAT = 0x02;
    const OBSERVER_FULL      = 0x03;
    const OBSERVER_REFEREE   = 0x04;

    // 4.7 [GameType]
    const TYPE_LADDER_FFA  = 0x01;
    const TYPE_CUSTOM      = 0x09;
    const TYPE_LOCAL       = 0x0D;
    const TYPE_LADDER_TEAM = 0x20;

    // 4.10 [GameStartRecord] - Select Mode
    const MODE_TEAM_RACE_SELECTABLE     = 0x00;
    const MODE_TEAM_NOT_SELECTABLE      = 0x01;
    const MODE_TEAM_RACE_NOT_SELECTABLE = 0x03;
    const MODE_RACE_FIXED_TO_RANDOM     = 0x04;
    const MODE_AUTOMATIC_MATCHMAKING    = 0xCC;

    // 4.11 [SlotRecord] - Slot Status
    const EMPTY  = 0x00;
    const CLOSED = 0x01;
    const USED   = 0x02;

    // 4.11 [SlotRecord] - Is Computer
    const PLAYER_HUMAN    = 0x00;
    const PLAYER_COMPUTER = 0x01;

    // 4.11 [SlotRecord] - Colour
    const RED       = 0x00;
    const BLUE      = 0x01;
    const TEAL      = 0x02;
    const PURPLE    = 0x03;
    const YELLOW    = 0x04;
    const ORANGE    = 0x05;
    const GREEN     = 0x06;
    const PINK      = 0x07;
    const GREY      = 0x08;
    const LIGHTBLUE = 0x09;
    const DARKGREEN = 0x0A;
    const BROWN     = 0x0B;
    const MAROON    = 0x0C;
    const NAVY      = 0x0D;
    const TURQUOISE = 0x0E;
    const VIOLET    = 0x0F;
    const WHEAT     = 0x10;
    const PEACH     = 0x11;
    const MINT      = 0x12;
    const LAVENDER  = 0x13;
    const COAL      = 0x14;
    const SNOW      = 0x15;
    const EMERALD   = 0x16;
    const PEANUT    = 0x17;

    // 4.11 [SlotRecord] - Player Race (Map Defined)
    const RACE_HUMAN    = 0x01;
    const RACE_ORC      = 0x02;
    const RACE_NIGHTELF = 0x04;
    const RACE_UNDEAD   = 0x08;
    const RACE_RANDOM   = 0x20;

    // 4.11 [SlotRecord] - AI Strength */
    const AI_EASY   = 0x01;
    const AI_NORMAL = 0x02;
    const AI_INSANE = 0x04;

    // 5.0 [ReplayData] - 0x17 LeaveGame: Leave Reason
    const REASON_CONN_CLOSED_REMOTE = 0x01;
    const REASON_CONN_CLOSED_LOCAL  = 0x0C;
    const REASON_UNKNOWN            = 0x0E;

    // 5.0 [ReplayData] - 0x2F Forced Game End Countdown: Mode
    const MODE_COUNTDOWN_RUNNING = 0x00;
    const MODE_COUNTDOWN_FORCED  = 0x01;

    // 5.0 [ReplayData] - 0x20 Player Chat Message
    const CHAT_FLAG_DELAYED_SCREEN = 0x10;
    const CHAT_FLAG_NORMAL         = 0x20;

    const CHAT_ALL      = 0x00;
    const CHAT_ALLIES   = 0x01;
    const CHAT_OBSERVER = 0x02;
    const CHAT_PRIVATE  = 0x03; // + N (N = slotNumber)

	public static function speed ($value)
	{
		return [
			self::SPEED_SLOW   => 'Slow',
			self::SPEED_NORMAL => 'Normal',
			self::SPEED_FAST   => 'Fast'
		] [$value] ?? NULL;
	}

	public static function visibility ($value)
	{
		return [
			self::VISIBILITY_HIDE_TERRAIN 	=> 'Hide Terrain',
			self::VISIBILITY_MAP_EXPLORED 	=> 'Map Explored',
			self::VISIBILITY_ALWAYS_VISIBLE => 'Always Visible',
			self::VISIBILITY_DEFAULT 		=> 'Default'
		] [$value] ?? NULL;
	}

	public static function observer ($value)
	{
		return [
			self::OBSERVER_NONE 	 => 'No Observers',
			self::OBSERVER_ON_DEFEAT => 'Observers on Defeat',
			self::OBSERVER_FULL 	 => 'Full Observers',
			self::OBSERVER_REFEREE 	 => 'Referees'
		] [$value] ?? NULL;
	}

	public static function gameType ($value)
	{
		return [
			self::TYPE_LADDER_FFA  => 'Ladder 1v1 / FFA',
			self::TYPE_CUSTOM 	   => 'Custom Game',
			self::TYPE_LOCAL 	   => 'Single Player / Local Game',
			self::TYPE_LADDER_TEAM => 'Ladder Team Game (AT / RT)',
		] [$value] ?? NULL;
	}

	public static function colour ($value)
	{
		return [
			self::RED 	    => 'Red',
			self::BLUE 	    => 'Blue',
			self::TEAL 	    => 'Teal',
			self::PURPLE 	=> 'Purple',
			self::YELLOW 	=> 'Yellow',
			self::ORANGE    => 'Orange',
			self::GREEN     => 'Green',
			self::PINK 	    => 'Pink',
			self::GREY 	    => 'Grey',
			self::LIGHTBLUE => 'Light-Blue',
			self::DARKGREEN => 'Dark-Green',
			self::BROWN 	=> 'Brown',
			self::MAROON 	=> 'Maroon',
			self::NAVY 	    => 'Navy',
			self::TURQUOISE => 'Turquoise',
			self::VIOLET 	=> 'Violet',
			self::WHEAT 	=> 'Wheat',
			self::PEACH 	=> 'Peach',
			self::MINT 	    => 'Mint',
			self::LAVENDER  => 'Lavender',
			self::COAL 	    => 'Coal',
			self::SNOW 	    => 'Snow',
			self::EMERALD   => 'Emerald',
			self::PEANUT    => 'Peanut'
		] [$value] ?? NULL;
	}

	public static function race ($value)
	{
		return [
			self::RACE_HUMAN    => 'Human',
			self::RACE_ORC      => 'Orc',
			self::RACE_NIGHTELF => 'Night Elf',
			self::RACE_UNDEAD   => 'Undead',
			self::RACE_RANDOM   => 'Random'
		] [$value] ?? NULL;
	}

	public static function ai ($value)
	{
		return [
			self::AI_EASY   => 'Easy',
			self::AI_NORMAL => 'Normal',
			self::AI_INSANE => 'Insane'
		] [$value] ?? NULL;
	}

	public static function mode ($value)
	{
		return [
			self::MODE_TEAM_RACE_SELECTABLE 	=> 'Team & Race Selectable',
			self::MODE_TEAM_NOT_SELECTABLE  	=> 'Team Not Selectable',
			self::MODE_TEAM_RACE_NOT_SELECTABLE => 'Team & Race Not Selectable',
			self::MODE_RACE_FIXED_TO_RANDOM 	=> 'Race Fixed to Random',
			self::MODE_AUTOMATIC_MATCHMAKING 	=> 'Automated Match Making (Ladder)'
		] [$value] ?? NULL;
	}

	public static function chat ($value)
	{
		return [
			self::CHAT_ALL      => 'All',
			self::CHAT_ALLIES   => 'Allies',
			self::CHAT_OBSERVER => 'Observers'
		] [$value] ?? 'Private';
	}

    public static function boolean ($value)
    {
        return $value ? 'Yes' : 'No';
    }

    public static function objectId ($stream)
    {
        if (! ($stream instanceof Stream)) {
            $stream = new Buffer (
                pack ('N', $stream)
            );
        }

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
}