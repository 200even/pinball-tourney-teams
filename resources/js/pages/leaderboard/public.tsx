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

interface Team {
    id: number;
    name: string;
    generated_name: string;
    total_points: number;
    games_played: number;
    position: number;
    player1: Player;
    player2: Player;
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

    const getPositionDisplay = (position: number) => {
        if (position === 1) return '🥇';
        if (position === 2) return '🥈';
        if (position === 3) return '🥉';
        return position.toString();
    };

    const getPositionClass = (position: number) => {
        if (position === 1) return 'bg-yellow-500/10 border-yellow-500/20 text-yellow-600 dark:text-yellow-400';
        if (position === 2) return 'bg-gray-400/10 border-gray-400/20 text-gray-600 dark:text-gray-400';
        if (position === 3) return 'bg-amber-600/10 border-amber-600/20 text-amber-600 dark:text-amber-400';
        return 'bg-muted/30 border-muted';
    };

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
                {/* Stats Bar */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">{standings.length}</div>
                            <p className="text-xs text-muted-foreground">Teams</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">{completedRounds.length}</div>
                            <p className="text-xs text-muted-foreground">Rounds</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-2xl font-bold">
                                {standings[0]?.total_points || 0}
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
                        <CardTitle className="flex items-center gap-2">
                            <Icon name="trophy" className="h-5 w-5" />
                            Team Standings
                        </CardTitle>
                        <CardDescription>
                            Combined scores from both team members
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {standings.length === 0 ? (
                            <div className="text-center py-12 text-muted-foreground">
                                <Icon name="trophy" className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <h3 className="text-lg font-medium mb-2">No teams yet</h3>
                                <p>Teams will appear here once they're created</p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {standings.map((team) => (
                                    <div 
                                        key={team.id}
                                        className={`flex items-center gap-4 p-4 rounded-lg border transition-all duration-200 ${getPositionClass(team.position)}`}
                                    >
                                        <div className="flex items-center justify-center w-12 h-12 rounded-full bg-background border font-bold text-lg">
                                            {getPositionDisplay(team.position)}
                                        </div>

                                        <div className="flex-1 min-w-0">
                                            <h3 className="font-semibold text-lg truncate">{team.name}</h3>
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <span>{team.player1.name}</span>
                                                <Icon name="plus" className="h-3 w-3" />
                                                <span>{team.player2.name}</span>
                                            </div>
                                        </div>

                                        <div className="text-right">
                                            <div className="text-2xl font-bold">{team.total_points}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {team.games_played} games
                                            </div>
                                        </div>
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
