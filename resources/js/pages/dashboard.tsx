import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Icon } from '@/components/ui/icon';
import Heading from '@/components/heading';
import { SharedData } from '@/types';

interface Tournament {
    id: number;
    name: string;
    status: 'active' | 'completed' | 'cancelled';
    teams_count: number;
    matchplay_tournament_id: string;
    qr_code_uuid: string;
    created_at: string;
}

interface DashboardProps {
    recentTournaments?: Tournament[];
    totalTournaments: number;
    activeTournaments: number;
    totalTeams: number;
}

export default function Dashboard({ 
    recentTournaments = [], 
    totalTournaments = 0, 
    activeTournaments = 0, 
    totalTeams = 0 
}: DashboardProps) {
    const { auth } = usePage<SharedData>().props;

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-green-500/10 text-green-500 border-green-500/20';
            case 'completed':
                return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
            case 'cancelled':
                return 'bg-red-500/10 text-red-500 border-red-500/20';
            default:
                return 'bg-gray-500/10 text-gray-500 border-gray-500/20';
        }
    };

    const hasMatchplayToken = auth.user.matchplay_api_token || false;

    return (
        <AppLayout>
            <Head title="Tournament Dashboard" />
            
            <div className="p-4 md:p-6 space-y-8">
                {/* Welcome Header */}
                <div className="space-y-2">
                    <Heading>Welcome back, {auth.user.name}!</Heading>
                    <p className="text-muted-foreground text-lg">
                        Manage your pinball tournaments and track team performance.
                    </p>
                </div>

                {/* API Token Warning */}
                {!hasMatchplayToken && (
                    <Card className="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-3">
                                <Icon name="alert-triangle" className="h-5 w-5 text-amber-500 mt-0.5" />
                                <div className="flex-1">
                                    <h4 className="font-medium text-amber-800 dark:text-amber-200">
                                        Setup Required
                                    </h4>
                                    <p className="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                        Add your Matchplay API token to start creating tournaments.
                                    </p>
                                    <Link href={route('profile.edit')} className="mt-3 inline-block">
                                        <Button size="sm" className="gap-2">
                                            <Icon name="settings" className="h-4 w-4" />
                                            Add API Token
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Quick Stats */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Tournaments</CardTitle>
                            <Icon name="trophy" className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalTournaments}</div>
                            <p className="text-xs text-muted-foreground">
                                All time tournaments
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Tournaments</CardTitle>
                            <Icon name="play" className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{activeTournaments}</div>
                            <p className="text-xs text-muted-foreground">
                                Currently running
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Teams</CardTitle>
                            <Icon name="users" className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalTeams}</div>
                            <p className="text-xs text-muted-foreground">
                                Teams created
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Quick Actions</CardTitle>
                            <Icon name="zap" className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <Link href={hasMatchplayToken ? route('tournaments.create') : route('profile.edit')}>
                                <Button size="sm" className="w-full gap-2">
                                    <Icon name="plus" className="h-4 w-4" />
                                    {hasMatchplayToken ? 'New Tournament' : 'Setup First'}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Tournaments */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Recent Tournaments</CardTitle>
                                    <CardDescription>
                                        Your latest tournament activity
                                    </CardDescription>
                                </div>
                                <Link href={route('tournaments.index')}>
                                    <Button variant="outline" size="sm" className="gap-2">
                                        <Icon name="eye" className="h-4 w-4" />
                                        View All
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {recentTournaments.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    <Icon name="trophy" className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                    <p className="font-medium">No tournaments yet</p>
                                    <p className="text-sm">Create your first tournament to get started</p>
                                    {hasMatchplayToken && (
                                        <Link href={route('tournaments.create')} className="mt-3 inline-block">
                                            <Button size="sm" className="gap-2">
                                                <Icon name="plus" className="h-4 w-4" />
                                                Create Tournament
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {recentTournaments.slice(0, 5).map((tournament) => (
                                        <div key={tournament.id} className="flex items-center gap-3 p-3 rounded-lg border">
                                            <Icon name="trophy" className="h-4 w-4 text-muted-foreground" />
                                            <div className="flex-1 min-w-0">
                                                <Link 
                                                    href={route('tournaments.show', tournament.id)}
                                                    className="font-medium hover:underline truncate block"
                                                >
                                                    {tournament.name}
                                                </Link>
                                                <p className="text-sm text-muted-foreground">
                                                    {tournament.teams_count} teams • ID: {tournament.matchplay_tournament_id}
                                                </p>
                                            </div>
                                            <Badge className={getStatusColor(tournament.status)}>
                                                {tournament.status}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Quick Start Guide */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Start Guide</CardTitle>
                            <CardDescription>
                                Get up and running with tournament tracking
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-start gap-3">
                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold">
                                    1
                                </div>
                                <div className="flex-1">
                                    <h4 className="font-medium">Setup API Token</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Add your Matchplay Events API token in settings
                                    </p>
                                    {!hasMatchplayToken && (
                                        <Link href={route('profile.edit')} className="mt-1 inline-block">
                                            <Button variant="outline" size="sm">
                                                Add Token
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold">
                                    2
                                </div>
                                <div className="flex-1">
                                    <h4 className="font-medium">Create Tournament</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Import a tournament from Matchplay Events
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold">
                                    3
                                </div>
                                <div className="flex-1">
                                    <h4 className="font-medium">Create Teams</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Pair players and generate team names
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold">
                                    4
                                </div>
                                <div className="flex-1">
                                    <h4 className="font-medium">Share Leaderboard</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Generate QR codes for players to view standings
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}