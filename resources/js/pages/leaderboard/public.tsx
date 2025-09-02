import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Icon } from '@/components/ui/icon';
import { Separator } from '@/components/ui/separator';

interface Player {
    id: number;
    name: string;
    matchplay_player_id: string;
}

interface RoundScore {
    round_number: number;
    round_name: string;
    player1_points: number;
    player2_points: number;
    total_points: number;
    round_status: string;
}

interface Team {
    id: number;
    name: string;
    generated_name: string;
    total_points: number;
    games_played: number;
    position: number;
    player1: Player;
    player2: Player;
    player3?: Player;
    player4?: Player;
    player1_individual_score: number;
    player1_games_played: number;
    player2_individual_score: number;
    player2_games_played: number;
    player3_individual_score?: number;
    player3_games_played?: number;
    player4_individual_score?: number;
    player4_games_played?: number;
    round_scores: RoundScore[];
    is_in_progress: boolean;
}

interface Round {
    id: number;
    name: string;
    round_number: number;
    status: string;
    completed_at?: string;
}

interface Tournament {
    id: number;
    name: string;
    description?: string;
    status: string;
    matchplay_tournament_id: string;
}

interface Props {
    tournament: Tournament;
    standings: Team[];
    completedRounds: Round[];
    lastUpdated: string;
}

export default function PublicLeaderboard({ tournament, standings, completedRounds, lastUpdated }: Props) {
    const [autoRefresh, setAutoRefresh] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [currentTime, setCurrentTime] = useState(new Date().toLocaleTimeString());

    const [expandedTeam, setExpandedTeam] = useState<number | null>(null);
    const [selectedRound, setSelectedRound] = useState<number | null>(null);

    // Auto-refresh every 30 seconds
    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            refreshData();
        }, 30000);

        return () => clearInterval(interval);
    }, [autoRefresh]);

    // Update current time every second
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentTime(new Date().toLocaleTimeString());
        }, 1000);

        return () => clearInterval(interval);
    }, []);

    const refreshData = async () => {
        setRefreshing(true);
        try {
            const response = await fetch(window.location.href + '/refresh', {
                headers: {
                    'Accept': 'application/json',
                },
            });
            
            if (response.ok) {
                // Update the page data (in a real app, you'd use state management)
                window.location.reload();
            }
        } catch (error) {
            console.error('Failed to refresh data:', error);
        } finally {
            setRefreshing(false);
        }
    };

    const isComplete = tournament.status === 'completed';
    
    const getPositionDisplay = (position: number, ties: number[] = []) => {
        // Only show medals for completed tournaments
        if (isComplete) {
            if (position === 1) return '🥇';
            if (position === 2) return '🥈';
            if (position === 3) return '🥉';
        }
        
        // Show tie indicator if there are multiple teams with same position
        const isTied = ties.filter(p => p === position).length > 1;
        return isTied ? `T${position}` : position.toString();
    };

    const getPositionClass = (position: number, isInProgress: boolean = false) => {
        let baseClass = '';
        
        if (isComplete) {
            if (position === 1) baseClass = 'bg-yellow-500/10 border-yellow-500/20 text-yellow-600 dark:text-yellow-400';
            else if (position === 2) baseClass = 'bg-gray-400/10 border-gray-400/20 text-gray-600 dark:text-gray-400';
            else if (position === 3) baseClass = 'bg-amber-600/10 border-amber-600/20 text-amber-600 dark:text-amber-400';
            else baseClass = 'bg-muted/30 border-muted';
        } else {
            baseClass = 'bg-muted/30 border-muted';
        }
        
        // Add in-progress styling
        if (isInProgress) {
            baseClass += ' border-l-4 border-l-blue-500 bg-blue-50/50 dark:bg-blue-950/20';
        }
        
        return baseClass;
    };

    // Calculate standings up to a specific round
    const getStandingsUpToRound = (roundNumber: number) => {
        return standings.map(team => {
            const roundScoresUpToRound = team.round_scores.filter(rs => rs.round_number <= roundNumber);
            const totalPointsUpToRound = roundScoresUpToRound.reduce((sum, rs) => sum + rs.total_points, 0);
            const player1PointsUpToRound = roundScoresUpToRound.reduce((sum, rs) => sum + rs.player1_points, 0);
            const player2PointsUpToRound = roundScoresUpToRound.reduce((sum, rs) => sum + rs.player2_points, 0);
            
            return {
                ...team,
                total_points: totalPointsUpToRound,
                player1_individual_score: player1PointsUpToRound,
                player2_individual_score: player2PointsUpToRound,
                round_scores: roundScoresUpToRound,
            };
        }).sort((a, b) => b.total_points - a.total_points).map((team, index) => ({
            ...team,
            position: index + 1,
        }));
    };

    // Get the standings to display (either full or up to selected round)
    const displayStandings = selectedRound !== null ? getStandingsUpToRound(selectedRound) : standings;
    
    // Calculate ties for display
    const positions = displayStandings.map(team => team.position);
    const ties = positions;

    return (
        <div className="min-h-screen bg-background">
            <Head title={`${tournament.name} - Live Leaderboard`} />
            
            {/* Header */}
            <div className="bg-card border-b sticky top-0 z-10">
                <div className="container mx-auto px-4 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex-1 min-w-0">
                            <h1 className="text-2xl font-bold truncate">{tournament.name}</h1>
                            <div className="flex items-center gap-3 text-sm text-muted-foreground">
                                <span>Live Leaderboard</span>
                                <Separator orientation="vertical" className="h-4" />
                                <span>{completedRounds.length} rounds completed</span>
                                <Separator orientation="vertical" className="h-4" />
                                <span className="flex items-center gap-1">
                                    <Icon name="clock" className="h-3 w-3" />
                                    {currentTime}
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={refreshData}
                                disabled={refreshing}
                                className="gap-2"
                            >
                                {refreshing ? (
                                    <>
                                        <Icon name="loader-2" className="h-4 w-4 animate-spin" />
                                        Refreshing...
                                    </>
                                ) : (
                                    <>
                                        <Icon name="refresh-cw" className="h-4 w-4" />
                                        Refresh
                                    </>
                                )}
                            </Button>

                            <Button
                                variant={autoRefresh ? "default" : "outline"}
                                size="sm"
                                onClick={() => setAutoRefresh(!autoRefresh)}
                                className="gap-2"
                            >
                                <Icon name={autoRefresh ? "pause" : "play"} className="h-4 w-4" />
                                {autoRefresh ? "Auto" : "Manual"}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="container mx-auto px-4 py-6 space-y-6">
                {/* Historical View Banner */}
                {selectedRound !== null && (
                    <div className="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div className="flex items-center gap-3">
                            <Icon name="history" className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            <div>
                                <h3 className="font-semibold text-blue-900 dark:text-blue-100">
                                    Historical View - Round {selectedRound}
                                </h3>
                                <p className="text-sm text-blue-700 dark:text-blue-300">
                                    Showing standings as they were after Round {selectedRound}: {completedRounds.find(r => r.round_number === selectedRound)?.name}
                                </p>
                            </div>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={() => setSelectedRound(null)}
                                className="ml-auto border-blue-300 text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-900/50"
                            >
                                Back to Current
                            </Button>
                        </div>
                    </div>
                )}

                {/* Stats Bar */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">{displayStandings.length}</div>
                            <p className="text-xs text-muted-foreground">Teams</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">
                                {selectedRound !== null ? selectedRound : completedRounds.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {selectedRound !== null ? 'Round' : 'Rounds'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">
                                {displayStandings[0]?.total_points || 0}
                            </div>
                            <p className="text-xs text-muted-foreground">Top Score</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-xs text-muted-foreground">Last Updated</div>
                            <p className="text-sm font-medium">
                                {new Date(lastUpdated).toLocaleTimeString()}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Leaderboard */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Icon name="trophy" className="h-5 w-5" />
                                Team Standings
                                {!isComplete && (
                                    <Badge variant="outline" className="ml-2">
                                        Live
                                    </Badge>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                {completedRounds.length > 0 && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">View After Round:</span>
                                        <select 
                                            className="px-2 py-1 text-sm border border-border rounded bg-background text-foreground"
                                            value={selectedRound || ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                setSelectedRound(value ? parseInt(value) : null);
                                                setExpandedTeam(null);
                                                // Clear expanded team when changing rounds
                                            }}
                                        >
                                            <option value="">Current Standings</option>
                                            {completedRounds.map(round => (
                                                <option key={round.id} value={round.round_number}>
                                                    After Round {round.round_number}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                            </div>
                        </CardTitle>
                        <CardDescription>
                            {selectedRound !== null 
                                ? `Standings after Round ${selectedRound} - ${completedRounds.find(r => r.round_number === selectedRound)?.name || ''}`
                                : 'Current tournament standings with round-by-round details'
                            }
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {displayStandings.length === 0 ? (
                            <div className="text-center py-12 text-muted-foreground">
                                <Icon name="trophy" className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <h3 className="text-lg font-medium mb-2">No teams yet</h3>
                                <p>Teams will appear here once they're created</p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {displayStandings.map((team) => (
                                    <div key={team.id}>
                                        <div 
                                            className={`flex items-center gap-4 p-4 rounded-lg border transition-all duration-200 ${team.round_scores.length > 0 ? 'cursor-pointer hover:bg-muted/30' : ''} ${getPositionClass(team.position, team.is_in_progress)}`}
                                            onClick={team.round_scores.length > 0 ? () => setExpandedTeam(expandedTeam === team.id ? null : team.id) : undefined}
                                        >
                                            <div className="flex items-center justify-center w-12 h-12 rounded-full bg-background border font-bold text-lg">
                                                {getPositionDisplay(team.position, ties)}
                                            </div>

                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-semibold text-lg truncate">{team.name}</h3>
                                                    {team.is_in_progress && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            <Icon name="play" className="h-3 w-3 mr-1" />
                                                            In Progress
                                                        </Badge>
                                                    )}
                                                </div>
                                                
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-4 text-sm text-muted-foreground flex-wrap">
                                                        <span className="flex items-center gap-1">
                                                            {team.player1.name}
                                                            <span className="font-mono text-xs">({team.player1_individual_score}pts)</span>
                                                        </span>
                                                        <Icon name="plus" className="h-3 w-3" />
                                                        <span className="flex items-center gap-1">
                                                            {team.player2.name}
                                                            <span className="font-mono text-xs">({team.player2_individual_score}pts)</span>
                                                        </span>
                                                        {team.player3 && (
                                                            <>
                                                                <Icon name="plus" className="h-3 w-3" />
                                                                <span className="flex items-center gap-1">
                                                                    {team.player3.name}
                                                                    <span className="font-mono text-xs">({team.player3_individual_score || 0}pts)</span>
                                                                </span>
                                                            </>
                                                        )}
                                                        {team.player4 && (
                                                            <>
                                                                <Icon name="plus" className="h-3 w-3" />
                                                                <span className="flex items-center gap-1">
                                                                    {team.player4.name}
                                                                    <span className="font-mono text-xs">({team.player4_individual_score || 0}pts)</span>
                                                                </span>
                                                            </>
                                                        )}
                                                        {team.round_scores.length > 0 && (
                                                            <Icon name="chevron-down" className={`h-4 w-4 transition-transform ${expandedTeam === team.id ? 'rotate-180' : ''}`} />
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {(team.player1_games_played || 0) + (team.player2_games_played || 0) + (team.player3_games_played || 0) + (team.player4_games_played || 0)} total games played
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="text-right">
                                                <div className="text-2xl font-bold">{team.total_points}</div>
                                                <div className="text-sm text-muted-foreground">
                                                    {team.games_played} games
                                                </div>
                                            </div>
                                        </div>

                                        {/* Expanded Round Details */}
                                        {expandedTeam === team.id && team.round_scores.length > 0 && selectedRound === null && (
                                            <div className="mt-2 ml-16 mr-4 space-y-2 border-l-2 border-muted pl-4">
                                                <div className="text-xs text-muted-foreground mb-2 font-medium">Round-by-round breakdown:</div>
                                                {team.round_scores.map((round) => (
                                                    <div key={round.round_number} className="flex items-center justify-between py-2 border-b border-muted/30 last:border-0">
                                                        <div className="flex items-center gap-3">
                                                            <Badge variant="outline" className="text-xs">
                                                                R{round.round_number}
                                                            </Badge>
                                                            <span className="text-sm font-medium">{round.round_name}</span>
                                                            {round.round_status === 'active' && (
                                                                <Badge variant="secondary" className="text-xs">
                                                                    <Icon name="play" className="h-3 w-3 mr-1" />
                                                                    Active
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center gap-4 text-sm">
                                                            <span className="text-muted-foreground">
                                                                {team.player1.name}: <span className="font-mono">{round.player1_points}</span>
                                                            </span>
                                                            <span className="text-muted-foreground">
                                                                {team.player2.name}: <span className="font-mono">{round.player2_points}</span>
                                                            </span>
                                                            <span className="font-bold">
                                                                Total: {round.total_points}
                                                            </span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        {/* Historical Round View Notice */}
                                        {selectedRound !== null && expandedTeam === team.id && (
                                            <div className="mt-2 ml-16 mr-4 p-3 bg-muted/30 rounded border border-muted text-sm text-muted-foreground">
                                                <Icon name="info" className="h-4 w-4 inline mr-2" />
                                                This shows standings after Round {selectedRound}. Switch to "Current" to see round-by-round details.
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Tournament Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Tournament Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex justify-between items-center">
                            <span className="text-muted-foreground">Status:</span>
                            <Badge variant="outline" className="capitalize">
                                {tournament.status}
                            </Badge>
                        </div>
                        <div className="flex justify-between items-center">
                            <span className="text-muted-foreground">Matchplay ID:</span>
                            <span className="font-mono text-sm">{tournament.matchplay_tournament_id}</span>
                        </div>
                        {tournament.description && (
                            <div>
                                <span className="text-muted-foreground">Description:</span>
                                <p className="text-sm mt-1">{tournament.description}</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Footer */}
                <div className="text-center text-sm text-muted-foreground py-4">
                    <p>Powered by Pinball Tournament Team Tracker</p>
                    {autoRefresh && (
                        <p className="flex items-center justify-center gap-1 mt-1">
                            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            Auto-refreshing every 30 seconds
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
