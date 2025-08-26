import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { type SharedData, type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import Heading from '@/components/heading';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'Manual Name Matching',
        href: '/settings/manual-name-matching',
    },
];

interface RankedPlayer {
    matchplay_player_id: string;
    position: number;
    points: number;
    current_name: string;
    has_real_name: boolean;
}

interface TournamentData {
    id: string;
    name: string;
    status: string;
}

interface PageProps {
    tournament_data?: TournamentData;
    ranked_players?: RankedPlayer[];
    should_redirect?: boolean;
}

export default function ManualNameMatching({ tournament_data, ranked_players, should_redirect }: PageProps) {
    const { auth, flash } = usePage<SharedData>().props;
    
    // Handle flash data for tournament_data and ranked_players
    const actualTournamentData = tournament_data || flash?.tournament_data;
    const actualRankedPlayers = ranked_players || flash?.ranked_players;
    
    console.log('Component props:', { 
        tournament_data, 
        ranked_players: ranked_players?.length || 0 
    });
    const [tournamentId, setTournamentId] = useState(actualTournamentData?.id || '');
    const [playerNames, setPlayerNames] = useState<Record<string, string>>(
        actualRankedPlayers?.reduce((acc, player) => ({
            ...acc,
            [player.matchplay_player_id]: player.has_real_name ? player.current_name : ''
        }), {}) || {}
    );
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    // Handle redirect after successful load to change URL while preserving data
    useEffect(() => {
        if (should_redirect && tournament_data && ranked_players) {
            // Use replace to change URL without triggering a new request
            window.history.replaceState(null, '', '/settings/manual-name-matching');
        }
    }, [should_redirect, tournament_data, ranked_players]);

    // Update player names when new ranked players data comes in
    useEffect(() => {
        if (actualRankedPlayers) {
            const newPlayerNames = actualRankedPlayers.reduce((acc, player) => ({
                ...acc,
                [player.matchplay_player_id]: player.has_real_name ? player.current_name : ''
            }), {});
            setPlayerNames(newPlayerNames);
            console.log('Updated player names from ranked players:', newPlayerNames);
        }
    }, [actualRankedPlayers]);

    const handleLoadTournament = (e: React.FormEvent) => {
        e.preventDefault();

        if (!tournamentId.trim()) {
            return;
        }

        console.log('Loading tournament:', tournamentId.trim());
        console.log('Route URL:', '/settings/manual-name-matching/load');

        setLoading(true);

        router.post(route('settings.manual-name-matching.load'), {
            matchplay_tournament_id: tournamentId.trim(),
        }, {
            onFinish: () => {
                console.log('Request finished');
                setLoading(false);
            },
            onSuccess: (page) => {
                console.log('Request successful:', page);
            },
            onError: (errors) => {
                console.log('Request errors:', errors);
            },
        });
    };

    const handleNameChange = (playerId: string, name: string) => {
        setPlayerNames(prev => ({ ...prev, [playerId]: name }));
    };

    const handleSaveNames = (e?: React.MouseEvent) => {
        console.log('handleSaveNames called');
        e?.preventDefault();
        e?.stopPropagation();
        
        if (!actualTournamentData) {
            console.log('No tournament data available');
            return;
        }

        const saveRoute = '/settings/manual-name-matching/save';
        console.log('Saving names...', { 
            tournament_id: actualTournamentData.id, 
            player_count: Object.keys(playerNames).length,
            method: 'POST',
            current_url: window.location.href,
            save_route: saveRoute
        });

        setSaving(true);

        // Get CSRF token from cookie (Laravel's default)
        function getCookie(name: string) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop()?.split(';').shift();
        }
        
        const token = getCookie('XSRF-TOKEN') ? decodeURIComponent(getCookie('XSRF-TOKEN') || '') : 
                     document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        console.log('CSRF token:', token ? 'found' : 'not found');
        console.log('About to fetch:', saveRoute);
        console.log('Fetch method: POST');
        
        // Create FormData for better Laravel compatibility
        const formData = new FormData();
        formData.append('matchplay_tournament_id', actualTournamentData.id);
        
        // Add each player name
        Object.entries(playerNames).forEach(([playerId, playerName]) => {
            if (playerName && playerName.trim()) {
                formData.append(`player_names[${playerId}]`, playerName.trim());
            }
        });
        
        // Add CSRF token
        if (token) {
            formData.append('_token', token);
        }
        
        // Use XMLHttpRequest which we know works
        const xhr = new XMLHttpRequest();
        xhr.open('POST', saveRoute);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.onload = function() {
            setSaving(false);
            if (xhr.status >= 200 && xhr.status < 300) {
                console.log('Names saved successfully');
                alert('Player names saved successfully!');
                window.location.reload();
            } else {
                console.log('Save failed with status:', xhr.status, 'Response:', xhr.responseText);
                alert('Failed to save names. Please try again.');
            }
        };
        
        xhr.onerror = function() {
            setSaving(false);
            console.log('XHR Error occurred');
            alert('Network error occurred. Please try again.');
        };
        
        xhr.send(formData);
    };

    const getPositionColor = (position: number) => {
        if (position === 1) return 'bg-yellow-500/20 text-yellow-700 border-yellow-500/30';
        if (position === 2) return 'bg-gray-400/20 text-gray-700 border-gray-400/30';
        if (position === 3) return 'bg-amber-600/20 text-amber-700 border-amber-600/30';
        if (position <= 8) return 'bg-blue-500/20 text-blue-700 border-blue-500/30';
        return 'bg-muted/50 text-muted-foreground border-muted';
    };

    const hasRequiredKeys = auth.user.matchplay_api_token;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manual Name Matching" />

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        title="Manual Name Matching"
                        description="Load any Matchplay tournament and manually assign real names to players"
                    />

                    {!hasRequiredKeys && (
                        <Alert variant="warning">
                            <Icon name="triangle-alert" className="h-4 w-4" />
                            <AlertDescription>
                                Please add your Matchplay API token in your{' '}
                                <a href={route('profile.edit')} className="underline">profile settings</a> to use this feature.
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

                    {flash?.error && (
                        <Alert variant="destructive">
                            <Icon name="x-circle" className="h-4 w-4" />
                            <AlertDescription>
                                <div className="space-y-2">
                                    <div className="font-medium">Error:</div>
                                    <div className="text-sm">{flash.error}</div>
                                </div>
                            </AlertDescription>
                        </Alert>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Icon name="search" className="h-5 w-5" />
                                Load Tournament
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleLoadTournament} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="tournament_id">
                                        Matchplay Tournament ID
                                    </Label>
                                    <Input
                                        id="tournament_id"
                                        type="text"
                                        placeholder="e.g., 194452, 206710"
                                        value={tournamentId}
                                        onChange={(e) => setTournamentId(e.target.value)}
                                        disabled={!hasRequiredKeys}
                                        required
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Enter any Matchplay tournament ID to load the final standings
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    disabled={!hasRequiredKeys || !tournamentId.trim() || loading}
                                    className="gap-2"
                                >
                                    {loading ? (
                                        <>
                                            <Icon name="loader-2" className="h-4 w-4 animate-spin" />
                                            Loading...
                                        </>
                                    ) : (
                                        <>
                                            <Icon name="download" className="h-4 w-4" />
                                            Load Tournament
                                        </>
                                    )}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {actualTournamentData && actualRankedPlayers && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            <Icon name="trophy" className="h-5 w-5" />
                                            {actualTournamentData.name}
                                        </CardTitle>
                                        <p className="text-sm text-muted-foreground mt-1">
                                            Tournament ID: {actualTournamentData.id} • {actualRankedPlayers.length} players
                                        </p>
                                    </div>
                                    <Badge variant="outline" className="capitalize">
                                        {actualTournamentData.status}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="text-sm text-muted-foreground">
                                        Edit player names below. Names that don't start with "Player" are already matched.
                                    </div>

                                    <Separator />

                                    <div className="space-y-3 max-h-96 overflow-y-auto">
                                        {actualRankedPlayers.map((player) => (
                                            <div
                                                key={player.matchplay_player_id}
                                                className="flex items-center gap-4 p-3 border rounded-lg bg-card"
                                            >
                                                <div className="flex items-center gap-3 min-w-0 flex-1">
                                                    <Badge
                                                        variant="outline"
                                                        className={`min-w-[3rem] justify-center font-bold ${getPositionColor(player.position)}`}
                                                    >
                                                        #{player.position}
                                                    </Badge>
                                                    
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <Label
                                                                htmlFor={`player-${player.matchplay_player_id}`}
                                                                className="text-sm font-medium"
                                                            >
                                                                ID: {player.matchplay_player_id}
                                                            </Label>
                                                            {player.has_real_name && (
                                                                <Icon name="check-circle" className="h-4 w-4 text-green-500" />
                                                            )}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {player.points} points
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="min-w-0 flex-1">
                                                    <Input
                                                        id={`player-${player.matchplay_player_id}`}
                                                        value={playerNames[player.matchplay_player_id] || ''}
                                                        onChange={(e) => handleNameChange(player.matchplay_player_id, e.target.value)}
                                                        placeholder="Enter real name..."
                                                        className="text-sm"
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <Separator />

                                    <div className="flex items-center justify-end gap-3">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                router.visit(route('settings.manual-name-matching'));
                                            }}
                                        >
                                            Clear
                                        </Button>
                                        <Button
                                            type="button"
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                handleSaveNames(e);
                                            }}
                                            disabled={saving}
                                            className="gap-2"
                                        >
                                            {saving ? (
                                                <>
                                                    <Icon name="loader-2" className="h-4 w-4 animate-spin" />
                                                    Saving...
                                                </>
                                            ) : (
                                                <>
                                                    <Icon name="save" className="h-4 w-4" />
                                                    Save Names
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
