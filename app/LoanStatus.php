<?php

namespace App;

enum LoanStatus: int
{
    case Requested = 1;

    case Approved = 2;
}
