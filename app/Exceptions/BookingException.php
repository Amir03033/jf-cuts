<?php

namespace App\Exceptions;

use DomainException;

/** Een boekingsregel is overtreden. Het bericht is bedoeld om aan de gebruiker te tonen. */
class BookingException extends DomainException
{
}
