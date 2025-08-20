import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Icon } from '@/components/ui/icon';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
];

interface Tournament {
    id: number;
    name: string;
    status: string;
    matchplay_tournament_id: string;
    start_date?: string;
    end_date?: string;
    qr_code_uuid: string;
    teams_count?: number;
    created_at: string;
}

interface PageProps {
    tournaments: Tournament[];
}

export default function TournamentsIndex({ tournaments }: PageProps) {
    const { auth } = usePage<SharedData>().props;

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
            <Head title="Tournaments" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading title="Tournaments" description="Manage your pinball tournaments and teams" />
                    
                    {auth.user.matchplay_api_token && (
                        <Link href={route('tournaments.create')}>
                            <Button className="gap-2">
                                <Icon name="plus" className="h-4 w-4" />
                                New Tournament
                            </Button>
                        </Link>
                    )}
                </div>

                {!auth.user.matchplay_api_token && (
                    <Card className="border-amber-500/20 bg-amber-500/5">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-4">
                                <div className="rounded-md bg-amber-500/10 p-2">
                                    <Icon name="alert-triangle" className="h-5 w-5 text-amber-500" />
                                </div>
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

                {tournaments.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <div className="rounded-full bg-muted p-4 mb-4">
                                <Icon name="trophy" className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <h3 className="text-lg font-semibold mb-2">No tournaments yet</h3>
                            <p className="text-muted-foreground text-center mb-6 max-w-md">
                                Create your first tournament to start tracking team performance and scores.
                            </p>
                            {auth.user.matchplay_api_token && (
                                <Link href={route('tournaments.create')}>
                                    <Button className="gap-2">
                                        <Icon name="plus" className="h-4 w-4" />
                                        Create Tournament
                                    </Button>
                                </Link>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {tournaments.map((tournament) => (
                            <Card key={tournament.id} className="group hover:shadow-md transition-shadow">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between">
                                        <div className="space-y-1 min-w-0 flex-1">
                                            <CardTitle className="line-clamp-1">{tournament.name}</CardTitle>
                                            <div className="flex items-center gap-2">
                                                <Badge 
                                                    variant="outline" 
                                                    className={`text-xs ${getStatusColor(tournament.status)}`}
                                                >
                                                    {tournament.status}
                                                </Badge>
                                                {tournament.teams_count !== undefined && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {tournament.teams_count} teams
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <Icon name="external-link" className="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors" />
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Matchplay ID:</span>
                                        <code className="px-2 py-1 bg-muted rounded text-xs">
                                            {tournament.matchplay_tournament_id}
                                        </code>
                                    </div>
                                    
                                    {tournament.start_date && (
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-muted-foreground">Start Date:</span>
                                            <span>{new Date(tournament.start_date).toLocaleDateString()}</span>
                                        </div>
                                    )}

                                    <div className="flex gap-2 pt-2">
                                        <Link 
                                            href={route('tournaments.show', tournament.id)}
                                            className="flex-1"
                                        >
                                            <Button variant="outline" size="sm" className="w-full gap-2">
                                                <Icon name="eye" className="h-4 w-4" />
                                                View
                                            </Button>
                                        </Link>
                                        <Link 
                                            href={route('tournaments.leaderboard.public', tournament.qr_code_uuid)}
                                            target="_blank"
                                        >
                                            <Button variant="outline" size="sm" className="gap-2">
                                                <Icon name="qr-code" className="h-4 w-4" />
                                                QR
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
