<?php

namespace w3lib\w3g\Translator;

use w3lib\Library\Logger;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\Library\Exception\RecoverableException;
use w3lib\w3g\Parser;
use w3lib\w3g\Lang;
use w3lib\w3g\Replay;
use w3lib\w3g\Model\Action;
use w3lib\w3g\Model\W3MMD;
use w3lib\w3g\Translator;

use function w3lib\Library\xxd;

/**
 * Dota replays use a modified w3mmd message format. This translator converts
 * the modified messages to the standard w3mmd message format.
 *
 * Unknown:
 *   Data Tower010 10
 *   Data Roshan 1
 *   Data AegisOn 7
 *   Data AegisOff 7
 *   Data Rax000 8
 *   Data Rax001 7
 */
class Dota
{
    const PREFIX = 'dr.x';

    const TYPE_GLOBAL = 'Global';
    const TYPE_DATA   = 'Data';

    const E_MODE = 'Mode';
    const E_POOL = 'Pool';
    const E_BAN  = 'Ban';
    const E_PICK = 'Pick';
    const E_PUI  = 'PUI_';
    const E_DRI  = 'DRI_';
    const E_START = 'GameStart';
    const E_RUNE = 'RuneUse';
    const E_LEVEL = 'Level';
    const E_ASSIST = 'Assist';
    const E_HERO_KILL = 'Hero';

    const V_CSK = 'CSK';
    const V_CSD = 'CSD';
    const V_NK = 'NK';

    const G_WINNER = 'Winner';

    const V_KILLS   = 'Kills';
    const V_DEATHS  = 'Deaths';
    const V_ASSISTS = 'Assists';

    /** **/

    // DefEvent {name} {argc} [{param1} {param2} ...] {format}
    const EVENTS = [

        // Data Modecd 0
        self::E_MODE => [
            'name' => 'mode',
            'argv' => [ 'value' ]
        ],

        // Data Pool{pid} {oid}
        // self::E_POOL => [
        //     'name' => 'pool',
        //     'argv' => [ 'pid', 'oid' ]
        // ],

        // Data Ban{pid} {oid}
        self::E_BAN => [
            'name' => 'ban',
            'argv' => [ 'player', 'hero' ]
        ],

        // Data Pick{pid} {oid}
        self::E_PICK => [
            'name' => 'pick',
            'argv' => [ 'player', 'hero' ]
        ],

        // Data PUI_{pid} {oid}
        // self::E_PUI => [
        //     'name' => 'pickupItem',
        //     'argv' => [ 'pid', 'oid' ]
        // ],

        // Data DRI_{pid} {oid}
        // self::E_DRI => [
        //     'name' => 'dropItem',
        //     'argv' => [ 'pid', 'oid' ]
        // ],

        // Data GameStart 1
        self::E_START => [
            'name' => 'gameStart',
            'argv' => []
        ],

        // Data RuneUse{rune} {pid}
        self::E_RUNE => [
            'name' => 'runeUse',
            'argv' => [ 'player', 'rune' ]
        ],

        // Data Level{level} {pid}
        self::E_LEVEL => [
            'name' => 'levelUp',
            'argv' => [ 'player', 'level' ]
        ],

        // Data Assist{assisterPid} {assistedPid}
        self::E_ASSIST => [
            'name' => 'assist',
            'argv' => [ 'player', 'assisted' ]
        ],

        // Data Hero{pidKilled} {pidKiller}
        self::E_HERO_KILL => [
            'name' => 'heroKill',
            'argv' => [ 'killed', 'killer' ]
        ]
    ];

    // DefVarP {varname} {type} {goal} {suggestion}
    const VARIABLES = [

        // {pid} 1 {int}
        '1' => 'kills',

        // {pid} 2 {int}
        '2' => 'deaths',

        // Data CSK{pid} {int}
        // {pid} 3 {int}
        '3' => 'creepKills',

        // Data CSD{pid} {int}
        // {pid} 4 {int}
        '4' => 'creepDenies',

        // {pid} 5 {int}
        '5' => 'assists',

        // {pid} 6 {int}
        '6' => 'gold',

        // Data NK{pid} {int}
        // {pid} 7 {int}
        '7' => 'neutralKills',

        // {pid} 8_{slot} {oid}
        '8_0' => 'item1',
        '8_1' => 'item2',
        '8_2' => 'item3',
        '8_3' => 'item4',
        '8_4' => 'item5',
        '8_5' => 'item6',

        // {pid} 9 {oid}
        '9' => 'hero',

        // < 6 = team 1, >= 6 = team 2
        // {pid} id {spawnId}
        'id' => 'team'

    ];

    const MODES = [
        'mm' => 'Mirror Match',
        'em' => 'Easy Mode',
        'ar' => 'All Random',
        'sp' => 'Shuffle Players',
        'nb' => 'No Bottom',
        'nt' => 'No Top',
        'ap' => 'All Pick',
        'cd' => 'Captain Draft'
    ];

    private static $teams = [
        '1' => [],
        '2' => []
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
            self::init ($buffer, $context);
            self::$initialized = TRUE;
        }

        /**
         * [0] => dr.x
         * [1] => Data|Global|{pid}
         * [2] => {key}
         * [3] => uint32
         */
        // xxd ($stream);

        $intro   = $stream->string ();
        $type    = $stream->string ();
        $varname = $stream->string ();
        $value   = $stream->uint32 ();

        $varname = new Buffer ($varname);

        // var_dump ($type . ' ' . $varname . ' ' . $value);

        switch ($type) {

            /** **/

            case self::TYPE_GLOBAL:
                switch ($varname) {
                    case self::G_WINNER:
                        // FlagPlayer

                        foreach (self::$teams as $team => $pids) {
                            foreach ($pids as $pid) {
                                self::pack (
                                    $buffer,

                                    sprintf (
                                        '%s %d %s',
                                        W3MMD::FLAGP,
                                        $pid,
                                        $team === $value ?
                                            W3MMD::FLAG_WINNER :
                                            W3MMD::FLAG_LOSER
                                    )
                                );
                            }
                        }
                    break;
                }
            break;

            /** **/

            case self::TYPE_DATA:
                if ($varname->startsWith (self::E_MODE)) {
                    $varname->read (self::E_MODE);

                    $value = $varname->string ();
                    $modes = [];

                    foreach (self::MODES as $code => $display) {
                        if (stripos ($value, $code) !== FALSE) {
                            $modes [] = $display;
                        }
                    }

                    self::event (
                        $buffer,
                        self::E_MODE,
                        implode (', ', $modes)
                    );
                } else if ($varname->startsWith (self::E_BAN)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_BAN),
                        self::getPlayerName ($context->replay, $varname), // {pid}
                        Lang::objectId ($value)
                    );
                } else if ($varname->startsWith (self::E_PICK)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_PICK),
                        self::getPlayerName ($context->replay, $varname), // {pid}
                        Lang::objectId ($value)
                    );
                } else if ($varname->startsWith (self::E_START)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_START)
                    );
                } else if ($varname->startsWith (self::E_RUNE)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_RUNE),
                        self::getPlayerName ($context->replay, $value),  // {pid}
                        $varname // {rune}
                    );
                } else if ($varname->startsWith (self::E_LEVEL)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_LEVEL),
                        self::getPlayerName ($context->replay, $value),  // {pid}
                        $varname // {level}
                    );
                } else if ($varname->startsWith (self::E_ASSIST)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_ASSIST),
                        self::getPlayerName ($context->replay, $varname), // {assister}
                        self::getPlayerName ($context->replay, $value)    // {assisted}
                    );

                    self::var (
                        $buffer,
                        $varname,
                        self::V_ASSISTS,
                        W3MMD::OP_ADD,
                        1
                    );
                } else if ($varname->startsWith (self::E_HERO_KILL)) {
                    self::event (
                        $buffer,
                        $varname->read (self::E_HERO_KILL),
                        self::getPlayerName ($context->replay, $value),  // {killer}
                        self::getPlayerName ($context->replay, $varname) // {killed}
                    );

                    self::var (
                        $buffer,
                        $value,
                        self::V_KILLS,
                        W3MMD::OP_ADD,
                        1
                    );

                    self::var (
                        $buffer,
                        $varname,
                        self::V_DEATHS,
                        W3MMD::OP_ADD,
                        1
                    );
                } else if ($varname->startsWith (self::V_CSK)) {
                    $varname->read (self::V_CSK);

                    self::var (
                        $buffer,
                        $varname, // {pid}
                        '3',      // creepKills
                        W3MMD::OP_ADD,
                        $value
                    );
                } else if ($varname->startsWith (self::V_CSD)) {
                    $varname->read (self::V_CSD);

                    self::var (
                        $buffer,
                        $varname, // {pid}
                        '4',      // creepDenies
                        W3MMD::OP_ADD,
                        $value
                    );
                } else if ($varname->startsWith (self::V_NK)) {
                    $varname->read (self::V_NK);

                    self::var (
                        $buffer,
                        $varname, // {pid}
                        '7',      // neutralKills
                        W3MMD::OP_ADD,
                        $value
                    );
                } else if (
                    $varname->startsWith (self::E_POOL)
                 || $varname->startsWith (self::E_PUI)
                 || $varname->startsWith (self::E_DRI)
                ) {
                    // Logger::debug ('Skipping known data event: [%s]', $varname);

                    throw new RecoverableException (
                        sprintf (
                            'Skipping known data event: [%s]',
                            $varname
                        )
                    );
                } else {
                    // Logger::warn ('Skipping unknown data event: [%s]', $varname);

                    throw new RecoverableException (
                        sprintf (
                            'Skipping unknown data event: [%s]',
                            $varname
                        )
                    );
                }
            break;

            /**
             * {pid} {code} {int|oid}
             */
            default:
                $pid = $type;

                if (   $varname->startsWith ('8_')
                    || $varname->startsWith ('9')) {
                    $value = Lang::objectId ($value);
                } else if ($varname->startsWith ('id')) {
                    $value = $value < 6 ? 1 : 2;

                    if (!in_array ($pid, self::$teams [$value])) {
                        self::$teams [$value] [] = $pid;
                    }
                }

                self::var (
                    $buffer,
                    $pid,
                    '' . $varname,
                    W3MMD::OP_SET,
                    $value
                );
            break;
        }

        $stream->prepend ($buffer);
    }

    protected static function init (Stream $stream, $context = NULL)
    {
        /* W3MMD::INIT_VERSION */
        self::pack (
            $stream,

            sprintf (
                '%s %s %d %d',
                W3MMD::INIT,
                W3MMD::INIT_VERSION,
                Parser::VERSION,
                W3MMD::VERSION
            )
        );

        /* W3MMD::INIT_PID */
        foreach ($context->replay->getPlayers () as $player) {
            self::pack (
                $stream,

                sprintf (
                    '%s %s %d %s',
                    W3MMD::INIT,
                    W3MMD::INIT_PID,
                    $player->colour,
                    $player->name
                )
            );
        }

        /* Initialize events. */
        foreach (self::EVENTS as $const => $event) {
            self::pack (
                $stream,

                // DefEvent {name} {argc} [{param1} {param2} ...] {format}
                sprintf (
                    '%s %s %d %s',
                    W3MMD::DEF_EVENT,
                    $event ['name'],
                    count ($event ['argv']),
                    implode (' ', $event ['argv'])

                    // Skip format for now...
                )
            );
        }

        /* Initialize variables. */
        foreach (self::VARIABLES as $code => $varname) {
            self::pack (
                $stream,

                // DefVarP {varname} {type} {goal} {suggestion}
                sprintf (
                    '%s %s %s %s %s',
                    W3MMD::DEF_VARP,
                    $varname,
                    W3MMD::TYPE_STRING,
                    W3MMD::GOAL_NONE,
                    W3MMD::SUGGEST_NONE
                )
            );
        }
    }

    protected static function event (Stream &$stream, $eventName, ... $argv)
    {
        if (isset (self::EVENTS [$eventName])) {
            $eventName = self::EVENTS [$eventName] ['name'];
        }

        self::pack (
            $stream,

            sprintf (
                '%s %s %s',
                W3MMD::EVENT,
                $eventName,
                implode (' ', $argv)
            )
        );
    }

    protected static function var (Stream &$stream, $pid, $varname, $operator, $value)
    {
        if (isset (self::VARIABLES [$varname])) {
            $varname = self::VARIABLES [$varname];
        }

        self::pack (
            $stream,

            sprintf (
                '%s %s %s %s %s',
                W3MMD::VARP,
                $pid,
                $varname,
                $operator,
                $value
            )
        );
    }

    protected static function getPlayerName (Replay $replay, $pid)
    {
        $player = $replay->getPlayerByColour ($pid);

        if (!$player) {
            return $pid;
        }

        return $player->name;
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