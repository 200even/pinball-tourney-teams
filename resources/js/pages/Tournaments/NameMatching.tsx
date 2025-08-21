import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'Name Matching',
        href: route('settings.name-matching'),
    },
];

export default function NameMatching() {
    const { auth, flash } = usePage<SharedData>().props;
    const [matchplayTournamentId, setMatchplayTournamentId] = useState('');
    const [ifpaTournamentId, setIfpaTournamentId] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!matchplayTournamentId.trim() || !ifpaTournamentId.trim()) {
            return;
        }

        setProcessing(true);

        router.post(route('settings.process-name-matching'), {
            matchplay_tournament_id: matchplayTournamentId.trim(),
            ifpa_tournament_id: parseInt(ifpaTournamentId.trim())
        }, {
            onFinish: () => setProcessing(false),
        });
    };

    const hasRequiredKeys = auth.user.matchplay_api_token && auth.user.ifpa_api_key;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tournament Name Matching" />
            
            <div className="space-y-6">
                <Heading 
                    title="Tournament Name Matching" 
                    description="Match player names between Matchplay and IFPA tournaments"
                />

                {!hasRequiredKeys && (
                    <Alert>
                        <Icon name="alert-circle" className="h-4 w-4" />
                        <AlertDescription>
                            You need both a Matchplay API token and IFPA API key to use this feature. 
                            Please add them in your{' '}
                            <Link 
                                href={route('profile.edit')} 
                                className="font-medium underline underline-offset-4 hover:no-underline"
                            >
                                profile settings
                            </Link>
                            .
                        </AlertDescription>
                    </Alert>
                )}

                {flash?.success && (
                    <Alert>
                        <Icon name="check-circle" className="h-4 w-4" />
                        <AlertDescription className="text-green-700 dark:text-green-400">
                            {flash.success}
                        </AlertDescription>
                    </Alert>
                )}

                {flash?.info && (
                    <Alert>
                        <Icon name="info" className="h-4 w-4" />
                        <AlertDescription className="text-blue-700 dark:text-blue-400">
                            {flash.info}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Icon name="zap" className="h-5 w-5" />
                                Match Tournament Names
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="matchplay_tournament_id">
                                        Matchplay Tournament ID
                                    </Label>
                                    <Input
                                        id="matchplay_tournament_id"
                                        type="text"
                                        placeholder="e.g., 206710"
                                        value={matchplayTournamentId}
                                        onChange={(e) => setMatchplayTournamentId(e.target.value)}
                                        disabled={!hasRequiredKeys}
                                        required
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        The Matchplay tournament where you want to update player names
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="ifpa_tournament_id">
                                        IFPA Tournament ID
                                    </Label>
                                    <Input
                                        id="ifpa_tournament_id"
                                        type="number"
                                        placeholder="e.g., 99448"
                                        value={ifpaTournamentId}
                                        onChange={(e) => setIfpaTournamentId(e.target.value)}
                                        disabled={!hasRequiredKeys}
                                        required
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        The IFPA tournament to get player names from
                                    </p>
                                </div>

                                <Button 
                                    type="submit" 
                                    disabled={!hasRequiredKeys || !matchplayTournamentId.trim() || !ifpaTournamentId.trim() || processing}
                                    className="w-full gap-2"
                                >
                                    {processing ? (
                                        <>
                                            <Icon name="loader-2" className="h-4 w-4 animate-spin" />
                                            Matching Names...
                                        </>
                                    ) : (
                                        <>
                                            <Icon name="link" className="h-4 w-4" />
                                            Match Player Names
                                        </>
                                    )}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Icon name="info" className="h-5 w-5" />
                                How It Works
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h4 className="font-medium mb-2">Step 1: Get Tournament IDs</h4>
                                <p className="text-sm text-muted-foreground">
                                    Find the Matchplay tournament ID from the URL (e.g., app.matchplay.events/tournaments/<strong>206710</strong>)
                                    and the IFPA tournament ID from IFPA website.
                                </p>
                            </div>

                            <div>
                                <h4 className="font-medium mb-2">Step 2: Match Names</h4>
                                <p className="text-sm text-muted-foreground">
                                    The system will find players who participated in both tournaments using their IFPA IDs
                                    and update the Matchplay player names with real names from IFPA.
                                </p>
                            </div>

                            <div>
                                <h4 className="font-medium mb-2">Step 3: Use for Teams</h4>
                                <p className="text-sm text-muted-foreground">
                                    Once names are matched, you can import the Matchplay tournament and create teams
                                    with real player names instead of "Player #ID".
                                </p>
                            </div>

                            <div className="pt-2 border-t">
                                <h4 className="font-medium mb-2">Example</h4>
                                <p className="text-sm text-muted-foreground">
                                    Matchplay: 206710 (recent tournament)
                                    <br />
                                    IFPA: 99448 (historical tournament with same players)
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
