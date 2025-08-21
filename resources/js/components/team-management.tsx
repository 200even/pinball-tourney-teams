import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Icon } from '@/components/ui/icon';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import InputError from '@/components/input-error';

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

interface Tournament {
    id: number;
    name: string;
    teams: Team[];
}

interface Props {
    tournament: Tournament;
    availablePlayers?: Player[];
}

export default function TeamManagement({ tournament, availablePlayers = [] }: Props) {
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [editingTeam, setEditingTeam] = useState<Team | null>(null);

    // Form for creating new teams
    const { data: createData, setData: setCreateData, post: createPost, processing: creating, errors: createErrors, reset: resetCreate } = useForm({
        player1_id: '',
        player2_id: '',
        custom_name: '',
    });

    // Form for editing team names
    const { data: editData, setData: setEditData, put: editPut, processing: editing, errors: editErrors } = useForm({
        name: '',
    });

    const createTeam = (e: React.FormEvent) => {
        e.preventDefault();
        createPost(route('tournaments.teams.store', tournament.id), {
            onSuccess: () => {
                setShowCreateForm(false);
                resetCreate();
            },
        });
    };

    const updateTeamName = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingTeam) return;

        editPut(route('tournaments.teams.update', [tournament.id, editingTeam.id]), {
            onSuccess: () => {
                setEditingTeam(null);
            },
        });
    };

    const deleteTeam = (team: Team) => {
        if (confirm(`Are you sure you want to delete the team "${team.name}"?`)) {
            router.delete(route('tournaments.teams.destroy', [tournament.id, team.id]));
        }
    };

    const regenerateTeamName = (team: Team) => {
        router.post(route('tournaments.teams.regenerate-name', [tournament.id, team.id]));
    };

    const startEditingTeam = (team: Team) => {
        setEditingTeam(team);
        setEditData({ name: team.name });
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            <Icon name="users" className="h-5 w-5" />
                            Teams ({tournament.teams.length})
                        </CardTitle>
                        <CardDescription>
                            Manage player pairs for this tournament
                        </CardDescription>
                    </div>
                    <Dialog open={showCreateForm} onOpenChange={setShowCreateForm}>
                        <DialogTrigger asChild>
                            <Button size="sm" className="gap-2">
                                <Icon name="plus" className="h-4 w-4" />
                                Add Team
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Create New Team</DialogTitle>
                                <DialogDescription>
                                    Select two players to form a team. A fun team name will be generated automatically.
                                </DialogDescription>
                            </DialogHeader>

                            <form onSubmit={createTeam} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="player1_id">Player 1 *</Label>
                                    <Select 
                                        value={createData.player1_id} 
                                        onValueChange={(value) => setCreateData('player1_id', value)}
                                    >
                                        <SelectTrigger className={createErrors.player1_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Select first player" />
                                        </SelectTrigger>
                                        <SelectContent className="max-h-60 overflow-y-auto" position="popper" side="bottom" sideOffset={4}>
                                            {availablePlayers.map((player) => (
                                                <SelectItem key={player.id} value={player.id.toString()}>
                                                    {player.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={createErrors.player1_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="player2_id">Player 2 *</Label>
                                    <Select 
                                        value={createData.player2_id} 
                                        onValueChange={(value) => setCreateData('player2_id', value)}
                                    >
                                        <SelectTrigger className={createErrors.player2_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Select second player" />
                                        </SelectTrigger>
                                        <SelectContent className="max-h-60 overflow-y-auto" position="popper" side="bottom" sideOffset={4}>
                                            {availablePlayers
                                                .filter(player => player.id.toString() !== createData.player1_id)
                                                .map((player) => (
                                                <SelectItem key={player.id} value={player.id.toString()}>
                                                    {player.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={createErrors.player2_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="custom_name">
                                        Custom Team Name 
                                        <span className="text-muted-foreground font-normal">(optional)</span>
                                    </Label>
                                    <Input
                                        id="custom_name"
                                        type="text"
                                        placeholder="Leave blank for auto-generated name"
                                        value={createData.custom_name}
                                        onChange={(e) => setCreateData('custom_name', e.target.value)}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        If left blank, a fun pinball-themed name will be generated
                                    </p>
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        onClick={() => setShowCreateForm(false)}
                                        disabled={creating}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={creating}>
                                        {creating ? (
                                            <>
                                                <Icon name="loader-2" className="h-4 w-4 animate-spin mr-2" />
                                                Creating...
                                            </>
                                        ) : (
                                            'Create Team'
                                        )}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </CardHeader>

            <CardContent>
                {tournament.teams.length === 0 ? (
                    <div className="text-center py-8 text-muted-foreground">
                        <Icon name="users" className="h-8 w-8 mx-auto mb-2 opacity-50" />
                        <p className="font-medium">No teams created yet</p>
                        <p className="text-sm">Create your first team to get started</p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {tournament.teams.map((team) => (
                            <div 
                                key={team.id}
                                className="flex items-center gap-3 p-4 rounded-lg border bg-card hover:shadow-sm transition-shadow"
                            >
                                <div className="flex-1 min-w-0">
                                    {editingTeam?.id === team.id ? (
                                        <form onSubmit={updateTeamName} className="space-y-2">
                                            <Input
                                                value={editData.name}
                                                onChange={(e) => setEditData('name', e.target.value)}
                                                className="font-medium"
                                            />
                                            <div className="flex gap-2">
                                                <Button type="submit" size="sm" disabled={editing}>
                                                    Save
                                                </Button>
                                                <Button 
                                                    type="button" 
                                                    variant="outline" 
                                                    size="sm"
                                                    onClick={() => setEditingTeam(null)}
                                                    disabled={editing}
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        </form>
                                    ) : (
                                        <>
                                            <h4 className="font-medium truncate">{team.name}</h4>
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <span>{team.player1.name}</span>
                                                <span>&</span>
                                                <span>{team.player2.name}</span>
                                            </div>
                                        </>
                                    )}
                                </div>

                                <div className="text-right">
                                    <div className="font-bold">{team.total_points} pts</div>
                                    <div className="text-xs text-muted-foreground">
                                        {team.games_played} games
                                    </div>
                                </div>

                                {editingTeam?.id !== team.id && (
                                    <div className="flex items-center gap-1">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => startEditingTeam(team)}
                                                >
                                                    <Icon name="edit" className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Edit team name</TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => regenerateTeamName(team)}
                                                >
                                                    <Icon name="shuffle" className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Generate new name</TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => deleteTeam(team)}
                                                    className="text-red-500 hover:text-red-600"
                                                >
                                                    <Icon name="trash-2" className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Delete team</TooltipContent>
                                        </Tooltip>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
