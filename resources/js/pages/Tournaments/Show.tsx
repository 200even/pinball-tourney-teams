import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';

import Heading from '@/components/heading';
import { Icon } from '@/components/ui/icon';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import PlayerNameEditor from '@/components/player-name-editor';
import QRCodeDisplay from '@/components/qr-code-display';

interface Player {
    id: number;
    name: string;
    matchplay_player_id: string;
    ifpa_id?: string;
}

interface Team {
    id: number;
    name: string;
    generated_name: string;
    total_points: number;
    games_played: number;
    player1: Player;
    player2: Player;
}

interface Round {
    id: number;
    round_number: number;
    status: string;
}

interface Tournament {
    id: number;
    name: string;
    status: string;
    matchplay_tournament_id: string;
    start_date?: string;
    end_date?: string;
    qr_code_uuid: string;
    auto_sync: boolean;
    teams: Team[];
    rounds: Round[];
}

interface Standing {
    position: number;
    team: Team;
    total_points: number;
    games_played: number;
}

interface PageProps {
    tournament: Tournament;
    standings: Standing[];
    qrCodeUrl: string;
    availablePlayers: Player[];
}

export default function TournamentShow({ tournament, standings, qrCodeUrl, availablePlayers }: PageProps) {
    const [syncingData, setSyncingData] = useState(false);
    const [togglingAutoSync, setTogglingAutoSync] = useState(false);
    const [showQRCode, setShowQRCode] = useState(false);

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

    const handleSyncData = () => {
        setSyncingData(true);
        router.post(route('tournaments.sync', tournament.id), {}, {
            preserveState: true,
            onFinish: () => setSyncingData(false),
        });
    };

    const handleToggleAutoSync = () => {
        setTogglingAutoSync(true);
        router.post(route('tournaments.toggle-auto-sync', tournament.id), {}, {
            preserveState: true,
            onFinish: () => setTogglingAutoSync(false),
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

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <Heading title={tournament.name} description="Tournament details and team standings" />
                    
                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            onClick={handleSyncData}
                            disabled={syncingData}
                            className="gap-2"
                        >
                            <Icon name={syncingData ? "loader-2" : "refresh-cw"} className={`h-4 w-4 ${syncingData ? 'animate-spin' : ''}`} />
                            {syncingData ? 'Syncing...' : 'Sync Data'}
                        </Button>
                        
                        <Button
                            variant="outline"
                            onClick={handleToggleAutoSync}
                            disabled={togglingAutoSync}
                            className="gap-2"
                        >
                            <Icon name={tournament.auto_sync ? "pause" : "play"} className="h-4 w-4" />
                            {togglingAutoSync ? 'Updating...' : (tournament.auto_sync ? 'Disable Auto-Sync' : 'Enable Auto-Sync')}
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => setShowQRCode(true)}
                            className="gap-2"
                        >
                            <Icon name="qr-code" className="h-4 w-4" />
                            QR Code
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Tabs defaultValue="standings" className="space-y-6">
                            <TabsList>
                                <TabsTrigger value="standings">Standings</TabsTrigger>
                                <TabsTrigger value="teams">Teams ({tournament.teams.length})</TabsTrigger>
                            </TabsList>

                            <TabsContent value="standings">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Current Standings</CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-0">
                                        {standings.length === 0 ? (
                                            <div className="p-6 text-center text-muted-foreground">
                                                No standings available yet. Teams will appear here once games are played.
                                            </div>
                                        ) : (
                                            <div className="space-y-0">
                                                {standings.map((standing, index) => (
                                                    <div
                                                        key={standing.team?.id || `standing-${index}`}
                                                        className={`flex items-center justify-between p-4 border-b last:border-b-0 ${
                                                            index < 3 ? 'bg-muted/30' : ''
                                                        }`}
                                                    >
                                                        <div className="flex items-center gap-4">
                                                            <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ${
                                                                index === 0 ? 'bg-yellow-500 text-yellow-50' :
                                                                index === 1 ? 'bg-gray-400 text-gray-50' :
                                                                index === 2 ? 'bg-amber-600 text-amber-50' :
                                                                'bg-muted text-muted-foreground'
                                                            }`}>
                                                                {standing.position}
                                                            </div>
                                                            <div>
                                                                <div className="font-medium">
                                                                    {standing.team?.name || standing.team?.generated_name || 'Unknown Team'}
                                                                </div>
                                                                <div className="text-sm text-muted-foreground">
                                                                    {standing.team?.player1?.name || 'Player 1'} & {standing.team?.player2?.name || 'Player 2'}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="font-bold">{standing?.total_points || 0} pts</div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {standing?.games_played || 0} games
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="teams">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <CardTitle>Teams</CardTitle>
                                        <Link href={route('tournaments.teams.index', tournament.id)}>
                                            <Button size="sm" className="gap-2">
                                                <Icon name="users" className="h-4 w-4" />
                                                Manage Teams
                                            </Button>
                                        </Link>
                                    </CardHeader>
                                    <CardContent className="p-0">
                                        {tournament.teams.length === 0 ? (
                                            <div className="p-6 text-center text-muted-foreground">
                                                No teams created yet.
                                                <Link href={route('tournaments.teams.index', tournament.id)} className="block mt-2">
                                                    <Button size="sm">Create Teams</Button>
                                                </Link>
                                            </div>
                                        ) : (
                                            <div className="space-y-0">
                                                {tournament.teams.map((team, index) => (
                                                    <div key={team?.id || `team-${index}`} className="flex items-center justify-between p-4 border-b last:border-b-0">
                                                        <div>
                                                            <div className="font-medium">
                                                                {team?.name || team?.generated_name || 'Unknown Team'}
                                                            </div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {team?.player1?.name || 'Player 1'} & {team?.player2?.name || 'Player 2'}
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="font-medium">{team?.total_points || 0} pts</div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {team?.games_played || 0} games
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Tournament Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Status:</span>
                                    <Badge variant="outline" className={getStatusColor(tournament.status)}>
                                        {tournament.status}
                                    </Badge>
                                </div>
                                
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Matchplay ID:</span>
                                    <code className="px-2 py-1 bg-muted rounded text-xs">
                                        {tournament.matchplay_tournament_id}
                                    </code>
                                </div>

                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Auto-Sync:</span>
                                    <Badge variant={tournament.auto_sync ? "default" : "secondary"}>
                                        {tournament.auto_sync ? "Enabled" : "Disabled"}
                                    </Badge>
                                </div>

                                {tournament.start_date && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Start Date:</span>
                                        <span>{new Date(tournament.start_date).toLocaleDateString()}</span>
                                    </div>
                                )}

                                {tournament.end_date && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">End Date:</span>
                                        <span>{new Date(tournament.end_date).toLocaleDateString()}</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Link href={route('tournaments.leaderboard.public', tournament.qr_code_uuid)} target="_blank" className="block">
                                    <Button variant="outline" className="w-full gap-2">
                                        <Icon name="external-link" className="h-4 w-4" />
                                        View Public Leaderboard
                                    </Button>
                                </Link>

                                <PlayerNameEditor tournament={tournament} players={availablePlayers} />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {showQRCode && (
                <QRCodeDisplay
                    url={qrCodeUrl}
                    title={`${tournament.name} Leaderboard`}
                    onClose={() => setShowQRCode(false)}
                />
            )}
        </AppLayout>
    );
}
