<?php

namespace App\Services;

class TeamNameGeneratorService
{
    private const ADJECTIVES = [
        'Bumpin\'', 'Flippin\'', 'Tiltin\'', 'Rollin\'', 'Spinnin\'', 'Bouncin\'',
        'Buzzin\'', 'Poppin\'', 'Slammin\'', 'Whizzing', 'Blazing', 'Electric',
        'Magnetic', 'Silver', 'Golden', 'Neon', 'Cosmic', 'Retro', 'Vintage',
        'Turbo', 'Super', 'Mega', 'Ultra', 'Maximum', 'Epic', 'Legendary',
        'Mighty', 'Steel', 'Chrome', 'Plasma', 'Digital', 'Cyber', 'Nuclear'
    ];

    private const NOUNS = [
        'Flippers', 'Bumpers', 'Plungers', 'Spinners', 'Targets', 'Ramps',
        'Slingshots', 'Kickers', 'Outlanes', 'Inlanes', 'Nudgers', 'Tilters',
        'Multiballers', 'Jackpots', 'Wizards', 'Magnets', 'Coils', 'Switches',
        'Bells', 'Chimes', 'Lights', 'Displays', 'Cabinets', 'Playfields',
        'Backglasses', 'Shooters', 'Skillshots', 'Combos', 'Modes', 'Features',
        'Bonuses', 'Multipliers', 'Orbits', 'Loops', 'Drops', 'Pops'
    ];

    private const SUFFIXES = [
        'Brigade', 'Squad', 'Crew', 'Team', 'Force', 'Club', 'Gang', 'Posse',
        'Alliance', 'Society', 'Union', 'Guild', 'Order', 'League', 'Coalition',
        'Dynasty', 'Empire', 'Syndicate', 'Collective', 'Assembly', 'Federation'
    ];

    public function generate(): string
    {
        $adjective = $this->getRandomElement(self::ADJECTIVES);
        $noun = $this->getRandomElement(self::NOUNS);
        $suffix = $this->getRandomElement(self::SUFFIXES);

        // Sometimes skip the suffix for variety
        if (rand(1, 3) === 1) {
            return "{$adjective} {$noun}";
        }

        return "{$adjective} {$noun} {$suffix}";
    }

    public function generateMultiple(int $count): array
    {
        $names = [];
        $attempts = 0;
        $maxAttempts = $count * 10; // Prevent infinite loops

        while (count($names) < $count && $attempts < $maxAttempts) {
            $name = $this->generate();
            if (!in_array($name, $names)) {
                $names[] = $name;
            }
            $attempts++;
        }

        return $names;
    }

    private function getRandomElement(array $array): string
    {
        return $array[array_rand($array)];
    }
}
