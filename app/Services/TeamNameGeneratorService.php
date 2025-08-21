<?php

namespace App\Services;

class TeamNameGeneratorService
{
    private array $adjectives = [
        'Lightning', 'Thunder', 'Silver', 'Golden', 'Electric', 'Sonic', 'Turbo', 'Super',
        'Mega', 'Ultra', 'Cosmic', 'Stellar', 'Blazing', 'Flaming', 'Frozen', 'Crystal',
        'Shadow', 'Phantom', 'Mystic', 'Epic', 'Legendary', 'Mighty', 'Wild', 'Fierce',
        'Noble', 'Royal', 'Ancient', 'Eternal', 'Infinite', 'Supreme'
    ];

    private array $nouns = [
        'Wizards', 'Champions', 'Warriors', 'Knights', 'Legends', 'Masters', 'Aces', 'Heroes',
        'Titans', 'Giants', 'Dragons', 'Phoenix', 'Eagles', 'Hawks', 'Wolves', 'Lions',
        'Tigers', 'Panthers', 'Cobras', 'Vipers', 'Sharks', 'Raiders', 'Crusaders', 'Gladiators',
        'Spartans', 'Vikings', 'Ninjas', 'Samurai', 'Guardians', 'Defenders'
    ];

    private array $pinballTerms = [
        'Flippers', 'Bumpers', 'Spinners', 'Ramps', 'Orbits', 'Plungers', 'Slingshots', 'Targets',
        'Kickers', 'Gates', 'Loops', 'Lanes', 'Drops', 'Poppers', 'Magnets', 'Captives',
        'Rollover', 'Standup', 'Rubber', 'Posts', 'Aprons', 'Outlanes', 'Inlanes', 'Skill Shot',
        'Multiball', 'Jackpot', 'Bonus', 'Extra Ball', 'Tilt', 'Nudge'
    ];

    public function generate(): string
    {
        $wordSets = [$this->adjectives, $this->nouns, $this->pinballTerms];
        $chosenSet = $wordSets[array_rand($wordSets)];
        
        // 70% chance for adjective + noun/term, 30% chance for just noun/term
        if (rand(1, 100) <= 70) {
            $adjective = $this->adjectives[array_rand($this->adjectives)];
            $noun = (rand(1, 100) <= 50) ? $this->nouns[array_rand($this->nouns)] : $this->pinballTerms[array_rand($this->pinballTerms)];
            return "{$adjective} {$noun}";
        } else {
            return $chosenSet[array_rand($chosenSet)];
        }
    }
}
