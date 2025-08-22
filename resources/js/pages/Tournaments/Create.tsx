import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Icon } from '@/components/ui/icon';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tournaments',
        href: '/tournaments',
    },
    {
        title: 'Create',
        href: '/tournaments/create',
    },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        matchplay_tournament_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('tournaments.store'));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Tournament" />

            <div className="p-4 md:p-6 space-y-6">
                <Heading title="Create Tournament" description="Import a tournament from Matchplay Events" />

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Icon name="plus" className="h-5 w-5" />
                            Tournament Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label htmlFor="matchplay_tournament_id">Matchplay Tournament ID</Label>
                                <Input
                                    id="matchplay_tournament_id"
                                    type="text"
                                    value={data.matchplay_tournament_id}
                                    onChange={(e) => setData('matchplay_tournament_id', e.target.value)}
                                    placeholder="Enter the tournament ID from Matchplay Events"
                                />
                                <InputError message={errors.matchplay_tournament_id} />
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Tournament'}
                                </Button>
                                <Link href={route('tournaments.index')}>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}