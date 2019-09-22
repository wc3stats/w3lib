<?php

namespace w3lib\w3g\Translator;

use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Parser;
use w3lib\w3g\Model\Action;
use w3lib\w3g\Model\W3MMD;
use w3lib\w3g\Translator;

use function w3lib\Library\xxd;

/**

    mode_map = {
        'mm': 'mirror_match',
        'em': 'easy_mode',
        'ar': 'all_random',
        'sp': 'shuffle_players',
        'nb': 'no_bottom',
        'nt': 'no_top',
        'ap': 'all_pick',
    }
def set_dota_player_values(dota_players, w3mmd_data, start, end):
    for index in range(start, end+1, 1):
        w3mmd = w3mmd_data[index]
        player_id_value = int(w3mmd[0].decode('utf-8'))
        key = w3mmd[1].decode('utf-8')
        value = w3mmd[2]

        if player_id_value > 5:
            player_id_value -= 1

        dota_player = dota_players[player_id_value-1]

        if key == '1':
            dota_player.kills = b2i(value)
        elif key == '2':
            dota_player.deaths = b2i(value)
        elif key == '3':
            dota_player.cskills = b2i(value)
        elif key == '4':
            dota_player.csdenies = b2i(value)
        elif key == '5':
            dota_player.assists = b2i(value)
        elif key == '6':
            dota_player.current_gold = b2i(value)
        elif key == '7':
            dota_player.neutral_kills = b2i(value)
        elif key == '8_0':
            dota_player.item1 = value
        elif key == '8_1':
            dota_player.item2 = value
        elif key == '8_2':
            dota_player.item3 = value
        elif key == '8_3':
            dota_player.item4 = value
        elif key == '8_4':
            dota_player.item5 = value
        elif key == '8_5':
            dota_player.item6 = value
        elif key == '9':
            dota_player.hero = value
        elif key == 'id':
            player_id_end = b2i(value)
            if player_id_end < 6:
                team = 1
            else:
                team = 2

            dota_player.player_id_end = player_id_end
            dota_player.team = team
        else:
            raise Exception("Not recognized key:", key)

    return dota_players

    dri = DROP ITEM
    pui = PICKUP ITEM


     for ts, (a, b, c) in replay_data['dota_events']:
        if a=='Data' and b.startswith('Mode'):
            global_data['modeline'] = b[4:]
        if a=='Data' and b.startswith('Hero'):
            victim_id = int(b.replace('Hero',''))
            killer_id = int(c)
            hero_data[killer_id]['kill_log'].append((ts, victim_id))
            hero_data[victim_id]['death_log'].append((ts, killer_id))
        if a=='Data' and b.startswith('Assist'):
            killer_id = int(b.replace('Assist', ''))
            victim_id = int(c)
            hero_data[killer_id]['assist_log'].append((ts, victim_id))
            hero_data[victim_id]['death_log_assist'].append((ts, killer_id))
        elif a=='Data' and b.startswith('Level'):
            hero_level = int(b.replace('Level', ''))
            hero_data[int(c)]['level_log'].append((ts, hero_level))
        elif a=='Data' and b.startswith('PUI_'):
            hero_id = int(b.replace('PUI_', ''))
            hero_data[hero_id]['item_log'].append((ts, 'PUI', c))
        elif a=='Data' and b.startswith('DRI_'):
            hero_id = int(b.replace('DRI_', ''))
            hero_data[hero_id]['item_log'].append((ts, 'DRI', c))
        elif a.isnumeric():
            hero_data[int(a)][key_names[str(b)]] = c
        elif a=='Global': # very rare
            global_data[b] = c


**/


/**
 * Dota replays use a modified w3mmd message format. This translator converts
 * the modified messages to the standard w3mmd message format.
 *
 * Unknown:
 *   [type:1-5] [key:id] [value:1-5]
 *   [type:9] [key:9] [value:1432510828]
 */
class Dota
{
    const PREFIX = 'dr.x';

    const TYPE_GLOBAL = 'Global';
    const TYPE_DATA   = 'Data';

    // DefEvent settings {argc} [{param1}, {param2}, ...] {format}
    const EVENTS = [
        // Data Modecd 0
        'mode',

        // Data Pool{pid} {oid}
        // 'pool',

        // Data Ban{pid} {oid}
        'ban',

        // Data Pick{pid} {oid}
        'pick',

        // Data PUI_{pid} {oid}
        // 'pickupItem',

        // Data DRI_{pid} {pid}
        // 'dropItem',


    ];

    // DefVarP {varname} {type} {goal} {suggestion}
    const VARIABLES = [
        // {pid} 9 {oid}
        'hero'
    ];

    /**
     * The first time we translate a message the W3MMD init messages need to
     * be packed.
     */
    public static $initialized = FALSE;

    public static function understands (Stream $stream)
    {
        return $stream->startsWith (self::PREFIX);
    }

    public static function translate (Stream &$stream, $context = NULL)
    {
        $buffer = new Buffer ();

        if (!self::$initialized) {
            /* W3MMD::INIT_VERSION */
            self::pack (
                $buffer,

                sprintf (
                    'init version %d %d',
                    Parser::VERSION,
                    W3MMD::VERSION
                )
            );

            /* W3MMD::INIT_PID */
            foreach ($context->replay->getPlayers () as $player) {
                self::pack (
                    $buffer,

                    sprintf (
                        'init pid %d %s',
                        $player->slot,
                        $player->name
                    )
                );
            }

            self::$initialized = TRUE;
        }

        /**
         * [0] => dr.x
         * [1] => Data
         * [2] => {key}
         * [3] => uint32
         */
        $intro = $stream->string ();
        $type  = $stream->string ();
        $key   = $stream->string ();
        $value = $stream->uint32 ();

        $key = new Buffer ($key);

        var_dump ($type . ' ' . $key . ' ' . $value);

        switch ($type) {
            case self::TYPE_GLOBAL:
                // var_dump ('GLOBAL ' . $key . ' ' . $value);
            break;

            case self::TYPE_DATA:
                // var_dump ('DATA ' . $key . ' ' . $value);
            break;

            /**
             * {pid} {type} {int|oid}
             *
             * Types:
             *   1   - Kills
             *   2   - Deaths
             *   3   - Creep Kills
             *   4   - Creep Denies
             *   5   - Assists
             *   6   - Gold
             *   7   - Neutral Kills
             *   8_0 - Item 1
             *   8_1 - Item 2
             *   8_3 - Item 3
             *   8_4 - Item 4
             *   8_5 - Item 5
             *   8_6 - Item 6
             *   9   - Hero
             */
            default:
                // var_dump ('OTHER ' . $type . ' ' . $key . ' ' . $value);
            break;
        }

        // var_dump ($intro);
        // var_dump ($type);
        // var_dump ($key);
        // var_dump ($value);
        // die ();

        $stream->prepend ($buffer);
    }

    protected static function pack (Stream $stream, $action)
    {
        $stream->append (Action::W3MMD, 'c');
        $stream->append (W3MMD::PREFIX);
        $stream->append (NULL, 'xx');
        $stream->append ($action);
        $stream->append (NULL, 'x');

        // 4 Unknown bytes.
        $stream->append (NULL, 'xxxx');
    }
}

?>