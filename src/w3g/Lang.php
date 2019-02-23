<?php

namespace w3lib\w3g;

use w3lib\w3g\Model\Game;
use w3lib\w3g\Model\Player;

class Lang
{
	public function speed ($value)
	{
		return [
			Game::SPEED_SLOW => 'Slow',
			Game::SPEED_NORMAL => 'Normal',
			Game::SPEED_FAST => 'Fast'
		] [$value] ?? NULL;
	}

	public function visibility ($value)
	{
		return [
			Game::VISIBILITY_HIDE_TERRAIN 	=> 'Hide Terrain',
			Game::VISIBILITY_MAP_EXPLORED 	=> 'Map Explored',
			Game::VISIBILITY_ALWAYS_VISIBLE => 'Always Visible',
			Game::VISIBILITY_DEFAULT 		=> 'Default'
		] [$value] ?? NULL;
	}

	public function observer ($value)
	{
		return [
			Game::OBSERVER_NONE 	 => 'No Observers',
			Game::OBSERVER_ON_DEFEAT => 'Observers on Defeat',
			Game::OBSERVER_FULL 	 => 'Full Observers',
			Game::OBSERVER_REFEREE 	 => 'Referees'
		] [$value] ?? NULL;
	}

	public function gameType ($value)
	{
		return [
			Game::TYPE_LADDER_FFA  => 'Ladder 1v1 / FFA',
			Game::TYPE_CUSTOM 	   => 'Custom Game',
			Game::TYPE_LOCAL 	   => 'Single Player / Local Game',
			Game::TYPE_LADDER_TEAM => 'Ladder Team Game (AT / RT)',
		] [$value] ?? NULL;
	}

	public function colour ($value)
	{
		return [
			Player::RED 	  => 'Red',
			Player::BLUE 	  => 'Blue',
			Player::TEAL 	  => 'Teal',
			Player::PURPLE 	  => 'Purple',
			Player::YELLOW 	  => 'Yellow',
			Player::ORANGE    => 'Orange',
			Player::GREEN     => 'Green',
			Player::PINK 	  => 'Pink',
			Player::GREY 	  => 'Grey',
			Player::LIGHTBLUE => 'Light Blue';
			Player::DARKGREEN => 'Dark Green';
			Player::BROWN 	  => 'Brown';
			Player::MAROON 	  => 'Maroon';
			Player::NAVY 	  => 'Navy',
			Player::TURQUOISE => 'Turquoise',
			Player::VIOLET 	  => 'Violet',
			Player::WHEAT 	  => 'Wheat',
			Player::PEACH 	  => 'Peach',
			Player::MINT 	  => 'Mint',
			Player::LAVENDER  => 'Lavender',
			Player::COAL 	  => 'Coal',
			Player::SNOW 	  => 'Snow',
			Player::EMERALD   => 'Emerald',
			Player::PEANUT    => 'Peanut'
		] [$value] ?? NULL;
	}

	public function race ($value)
	{
		return [
			Game::RACE_HUMAN => 'Human',
			Game::RACE_ORC => 'Orc',
			Game::RACE_NIGHTELF => 'Night Elf',
			Game::RACE_UNDEAD => 'Undead',
			Game::RACE_RANDOM => 'Random'
		] [$value] ?? NULL;
	}

	public function ai ($value)
	{
		return [
			Game::AI_EASY   => 'Easy',
			Game::AI_NORMAL => 'Normal',
			Game::AI_INSANE => 'Insane'
		] [$value] ?? NULL;
	}

	public function mode ($value)
	{
		return [
			Game::MODE_TEAM_RACE_SELECTABLE 	=> 'Team & Race Selectable',
			Game::MODE_TEAM_NOT_SELECTABLE  	=> 'Team Not Selectable',
			Game::MODE_TEAM_RACE_NOT_SELECTABLE => 'Team & Race Not Selectable',
			Game::MODE_RACE_FIXED_TO_RANDOM 	=> 'Race Fixed to Random',
			Game::MODE_AUTOMATIC_MATCHMAKING 	=> 'Automated Match Making (Ladder)'
		] [$value] ?? NULL;
	}

	public function chat ($value)
	{
		return [
			Game::CHAT_ALL       => 'All',
			Game::CHAT_ALLIES    => 'Allies',
			Game::CHAT_OBSERVERS => 'Observers',
			Game::CHAT_PASUED    => 'Paused',
			Game::CHAT_RESUMED   => 'Resumed'
		] [$value] ?? 'Private';
	}
}