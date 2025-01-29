<?php

namespace App;

enum CollectedMoneyStatus: int
{
    case FullyCollected = 1;

    case PartiallyCollected = 2;

    case OverCollected = 3;

    case NotCollected = 4;
}
