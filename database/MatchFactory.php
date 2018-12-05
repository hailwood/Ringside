<?php

use Carbon\Carbon;
use App\Models\Event;
use App\Models\Match;
use App\Models\Roster\Referee;
use App\Models\Roster\Wrestler;
use App\Models\MatchType;
use App\Models\Stipulation;
use App\Models\Championship;

class MatchFactory
{
    public $event_id = null;
    public $match_type_id = null;
    public $stipulation_id = null;
    public $champion = null;
    public $wrestlers;
    public $titles;
    public $referees;
    public $states = null;
    public $eventDate = null;

    public function __construct()
    {
        $this->populateDefaults();
    }

    public function states($states)
    {
        $this->states = $states;

        return $this;
    }

    public function create()
    {
        $match = factory(Match::class)->create([
            'event_id' => $this->event_id ?? factory(Event::class)->create(['date' => $this->eventDate]),
            'match_type_id' => $this->match_type_id ?? factory(MatchType::class)->create(),
            'stipulation_id' => $this->stipulation_id ?? null,
        ]);

        $this->addWrestlersForMatch($match);

        if ($this->titles->isNotEmpty()) {
            $match->addTitles($this->titles->pluck('id'));
        }

        $this->populateDefaults();

        return $match;
    }

    public function forEvent(Event $event)
    {
        $this->event_id = $event->id;

        return $this;
    }

    public function forMatchNumber($matchNumber)
    {
        $this->match_number = $match_number;

        return $this;
    }

    public function withMatchType(MatchType $matchtype)
    {
        $this->match_type_id = $matchtype->id;

        return $this;
    }

    public function withStipulation(Stipulation $stipulation)
    {
        $this->stipulation_id = $stipulation->id;

        return $this;
    }

    public function withTitle($title)
    {
        $this->titles->push($title);

        return $this;
    }

    public function withTitles($titles)
    {
        $merged = $this->titles->merge($titles);

        $this->titles = $merged;

        return $this;
    }

    public function withWrestlers($wrestlers)
    {
        $merged = $this->wrestlers->merge($wrestlers);

        $this->wrestlers = $merged;

        return $this;
    }

    public function withWrestler(Wrestler $wrestler)
    {
        $this->wrestlers->push($wrestler);

        return $this;
    }

    public function withChampion(Wrestler $wrestler)
    {
        $this->titles->each(function ($title, $key) use ($wrestler) {
            factory(Championship::class)->create([
                'wrestler_id' => $wrestler->id,
                'title_id' => $title->id,
                'won_on' => $title->introduced_at->copy()->subMonths(4),
            ]);
        });

        $concatenated = $this->wrestlers->concat([$wrestler]);

        $this->wrestlers = $concatenated;

        return $this;
    }

    public function addWrestlersForMatch($match)
    {
        if ($this->wrestlers->isEmpty()) {
            $numWrestlersToAddToMatch = $match->type->total_competitors;
        } else {
            $numWrestlersToAddToMatch = $match->type->total_competitors - $this->wrestlers->count();
        }

        $wrestlersForMatch = factory(Wrestler::class, (int) $numWrestlersToAddToMatch)->create(['hired_at' => $match->date->copy()->subMonths(2)]);
        $concatenatedWrestlers = $this->wrestlers->merge($wrestlersForMatch);
        $this->wrestlers = $concatenatedWrestlers;

        $splitWrestlers = $this->wrestlers->pluck('id')->split($match->type->number_of_sides)->flatten(1);

        $match->addWrestlers($splitWrestlers);
    }

    /**
     * @param $referees
     * @param $match
     */
    public static function addRefereesForMatch($referees, $match)
    {
        $refereesForMatch = collect($referees);
        // Check to see if we are trying to add multiple referees for a match that doesn't need multiple referees.

        $requiredReferees = $match->type->needsMultipleReferees() ? 2 : 1;
        $refereesToCreate = $requiredReferees - $refereesForMatch->count();
        if ($refereesForMatch->count() >= $requiredReferees) {
            throw new Exception('Too many referees trying to be adding to a match.');
        }

        if ($refereesToCreate) {
            $refereesForMatch = $refereesForMatch->push(factory(Referee::class, $refereesToCreate)->create());
        }

        $match->addReferees($refereesForMatch);
    }

    public function scheduled()
    {
        $this->eventDate = Carbon::tomorrow();

        return $this;
    }

    public function past()
    {
        $this->eventDate = Carbon::yesterday();

        return $this;
    }

    public function populateDefaults()
    {
        $this->wrestlers = collect();
        $this->titles = collect();
        $this->referees = collect();
    }
}
