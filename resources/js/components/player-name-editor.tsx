import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

interface Player {
    id: number;
    name: string;
    matchplay_player_id: string;
}

interface Tournament {
    id: number;
    name: string;
}

interface PlayerNameEditorProps {
    tournament: Tournament;
    players: Player[];
}



export default function PlayerNameEditor({ tournament, players }: PlayerNameEditorProps) {
    const { auth } = usePage<SharedData>().props;
    const [isOpen, setIsOpen] = useState(false);
    const [playerNames, setPlayerNames] = useState<Record<number, string>>(
        (players || []).reduce((acc, player) => ({ ...acc, [player.id]: player.name }), {})
    );

    const [saving, setSaving] = useState(false);

    const handleNameChange = (playerId: number, name: string) => {
        setPlayerNames(prev => ({ ...prev, [playerId]: name }));
    };



    const handleSave = () => {
        setSaving(true);
        
        const playersData = (players || []).map(player => ({
            id: player.id,
            name: playerNames[player.id] || player.name,
        }));

        router.post(route('tournaments.update-player-names', tournament.id), {
            players: playersData,
        }, {
            onSuccess: () => {
                setIsOpen(false);
                setSaving(false);
            },
            onError: () => {
                setSaving(false);
            },
        });
    };





    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="gap-2">
                    <Icon name="edit" className="h-4 w-4" />
                    Edit Player Names
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Icon name="edit" className="h-5 w-5" />
                        Edit Player Names - {tournament.name}
                    </DialogTitle>
                </DialogHeader>
                
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            <p>Update player names to make team selection easier.</p>
                        </div>
                    </div>

                    <Separator />

                    <div className="space-y-3">
                        <h4 className="font-medium">Need better player names?</h4>
                        <p className="text-sm text-muted-foreground">
                            Use the <strong>Manual Name Matching</strong> tool in Settings to load historical tournaments and manually assign player names.
                        </p>
                        <div className="flex gap-2">
                            <Link href={route('settings.manual-name-matching')}>
                                <Button variant="outline" className="gap-2">
                                    <Icon name="settings" className="h-4 w-4" />
                                    Go to Manual Name Matching
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        {(players || []).map(player => (
                            <Card key={player.id}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">
                                        Player {(players || []).indexOf(player) + 1}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        <div className="space-y-2">
                                            <Label htmlFor={`player-name-${player.id}`} className="text-sm">
                                                Display Name
                                            </Label>
                                            <Input
                                                id={`player-name-${player.id}`}
                                                value={playerNames[player.id] || ''}
                                                onChange={(e) => handleNameChange(player.id, e.target.value)}
                                                placeholder="Enter player name..."
                                                className="text-sm"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <div className="flex items-center justify-end gap-3 pt-4 border-t">
                        <Button variant="outline" onClick={() => setIsOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleSave} disabled={saving} className="gap-2">
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
            </DialogContent>
        </Dialog>
    );
}
