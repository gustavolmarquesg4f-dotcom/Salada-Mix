<?php

namespace App\Console\Commands;

use App\Domain\Orders\ReservationManager;
use Illuminate\Console\Command;

class ExpireOrderReservations extends Command
{
    protected $signature = 'marketplace:expire-reservations {--limit=50 : Maximum reservations per batch}';

    protected $description = 'Release expired draft holds safely and idempotently';

    public function handle(ReservationManager $manager): int
    {
        $limit = (int) $this->option('limit');

        if ($limit < 1 || $limit > 500) {
            $this->error('The batch limit must be between 1 and 500.');

            return self::FAILURE;
        }

        $released = 0;
        for ($batch = 0; $batch < 10; $batch++) {
            $count = $manager->expireDue($limit);
            $released += $count;

            if ($count < $limit) {
                break;
            }
        }

        $this->info('Expired reservations released: '.$released);

        return self::SUCCESS;
    }
}

