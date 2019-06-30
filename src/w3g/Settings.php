<?php

namespace w3lib\w3g;

class Settings
{
    /* Replays usually will have thousands of actions. Storing each action and
       its parsed components is highly memory intensive. Note that even when
       this is set to off, actions will still be parsed to calculate player APM,
       however, they will not be stored in the player models. */
    public $keepActions = FALSE;

    /* Actions per X configures the size of each action counter window in
       seconds. For example, a value of 60 indicates that actions counts will be
       split into buckets of size 60 seconds (apm). */
    public $apx = 60;
}

?>