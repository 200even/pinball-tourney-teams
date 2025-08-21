import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import Heading from '@/components/heading';
import TeamManagement from '@/components/team-management';
import QrCodeDisplay from '@/components/qr-code-display';
import PlayerNameEditor from '@/components/player-name-editor';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Icon } from '@/components/ui/icon';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';

interface Tournament {
    id: number;
    name: string;
    status: string;
    matchplay_tournament_id: string;
    description?: string;
    start_date?: string;
    end_date?: string;
    qr_code_uuid: string;
    teams: Team[];
    rounds: Round[];
    created_at: string;
}

interface Team {
    id: number;
    name: string;
    generated_name: string;
    total_points: number;
    games_played: number;
    position?: number;
    player1: Player;
    player2: Player;
}

interface Player {
    id: number;
    name: string;
    matchplay_player_id: string;
    ifpa_id?: string;
}

interface Round {
    id: number;
    round_number: number;
    name: string;
    status: string;
    completed_at?: string;
}

interface PageProps {
    tournament: Tournament;
    standings?: Team[];
    qrCodeUrl?: string;
    availablePlayers?: Player[];
}

export default function TournamentShow({ tournament, standings, qrCodeUrl, availablePlayers }: PageProps) {
    const { auth } = usePage<SharedData>().props;
    const [syncing, setSyncing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tournaments',
            href: '/tournaments',
        },
        {
            title: tournament.name,
            href: `/tournaments/${tournament.id}`,
        },
    ];

    const handleSync = () => {
        setSyncing(true);
        router.post(route('tournaments.sync', tournament.id), {}, {
            preserveScroll: true,
            onFinish: () => setSyncing(false),
        });
    };

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'active':
                return 'bg-green-500/10 text-green-500 border-green-500/20';
            case 'completed':
                return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
            case 'draft':
                return 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20';
            default:
                return 'bg-gray-500/10 text-gray-500 border-gray-500/20';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={tournament.name} />

            <div className="space-y-6">
                <div className="flex items-start justify-between">
                    <div className="space-y-1">
                        <div className="flex items-center gap-4">
                            <Link href={route('tournaments.index')}>
                                <Button variant="outline" size="sm" className="gap-2">
                                    <Icon name="arrow-left" className="h-4 w-4" />
                                    Back
                                </Button>
                            </Link>
                            <Heading title={tournament.name} description={tournament.description} />
                        </div>
                        <div className="flex items-center gap-4 ml-16">
                            <Badge 
                                variant="outline" 
                                className={getStatusColor(tournament.status)}
                            >
                                {tournament.status}
                            </Badge>
                            <span className="text-sm text-muted-foreground">
                                ID: {tournament.matchplay_tournament_id}
                            </span>
                            {tournament.teams && tournament.teams.length > 0 && (
                                <span className="text-sm text-muted-foreground">
                                    {tournament.teams.length} teams
                                </span>
                            )}
                        </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={handleSync}
                            disabled={syncing}
                            className="gap-2"
                        >
                            {syncing ? (
                                <>
                                    <Icon name="loader-2" className="h-4 w-4 animate-spin" />
                                    Syncing...
                                </>
                            ) : (
                                <>
                                    <Icon name="refresh-cw" className="h-4 w-4" />
                                    Sync Data
                                </>
                            )}
                        </Button>
                    </div>
                </div>

                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="overview" className="gap-2">
                            <Icon name="info" className="h-4 w-4" />
                            Overview
                        </TabsTrigger>
                        <TabsTrigger value="teams" className="gap-2">
                            <Icon name="users" className="h-4 w-4" />
                            Teams
                        </TabsTrigger>
                        <TabsTrigger value="leaderboard" className="gap-2">
                            <Icon name="trophy" className="h-4 w-4" />
                            Leaderboard
                        </TabsTrigger>
                        <TabsTrigger value="qr-code" className="gap-2">
                            <Icon name="qr-code" className="h-4 w-4" />
                            QR Code
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">Tournament Details</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Status:</span>
                                        <Badge variant="outline" className={getStatusColor(tournament.status)}>
                                            {tournament.status}
                                        </Badge>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Matchplay ID:</span>
                                        <code className="text-sm">{tournament.matchplay_tournament_id}</code>
                                    </div>
                                    {tournament.start_date && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Start Date:</span>
                                            <span className="text-sm">{new Date(tournament.start_date).toLocaleDateString()}</span>
                                        </div>
                                    )}
                                    {tournament.end_date && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">End Date:</span>
                                            <span className="text-sm">{new Date(tournament.end_date).toLocaleDateString()}</span>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">Statistics</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Teams:</span>
                                        <span className="font-medium">{tournament.teams?.length || 0}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Rounds:</span>
                                        <span className="font-medium">{tournament.rounds?.length || 0}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Players:</span>
                                        <span className="font-medium">{(tournament.teams?.length || 0) * 2}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">Quick Actions</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <Link 
                                        href={route('tournaments.leaderboard.public', tournament.qr_code_uuid)}
                                        target="_blank"
                                        className="block"
                                    >
                                        <Button variant="outline" size="sm" className="w-full gap-2">
                                            <Icon name="external-link" className="h-4 w-4" />
                                            View Public Leaderboard
                                        </Button>
                                    </Link>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={handleSync}
                                        disabled={syncing}
                                        className="w-full gap-2"
                                    >
                                        <Icon name="refresh-cw" className="h-4 w-4" />
                                        Sync with Matchplay
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="teams" className="space-y-4">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-lg font-semibold">Team Management</h3>
                                <p className="text-sm text-muted-foreground">Create and manage teams for this tournament</p>
                            </div>
                            {availablePlayers && (
                                <PlayerNameEditor tournament={tournament} players={availablePlayers} />
                            )}
                        </div>
                        <TeamManagement tournament={tournament} availablePlayers={availablePlayers} />
                    </TabsContent>

                    <TabsContent value="leaderboard" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Icon name="trophy" className="h-5 w-5" />
                                    Team Standings
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!tournament.teams || tournament.teams.length === 0 ? (
                                    <div className="text-center py-8">
                                        <Icon name="users" className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                        <p className="text-muted-foreground">No teams created yet.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {tournament.teams
                                            .sort((a, b) => b.total_points - a.total_points)
                                            .map((team, index) => (
                                                <div 
                                                    key={team.id}
                                                    className="flex items-center justify-between p-4 border rounded-lg"
                                                >
                                                    <div className="flex items-center gap-4">
                                                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-muted text-sm font-bold">
                                                            {index + 1}
                                                        </div>
                                                        <div>
                                                            <p className="font-medium">{team.name}</p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {team.player1.name} & {team.player2.name}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="font-bold">{team.total_points} pts</p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {team.games_played} games
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="qr-code" className="space-y-4">
                        <QrCodeDisplay tournament={tournament} />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}