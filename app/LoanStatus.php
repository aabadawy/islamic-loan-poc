<?php

namespace App;

enum LoanStatus: int
{
    case Requested = 1;

    case Approved = 2;

    case Paid = 3;

    case Partial_Paid = 4;
}
