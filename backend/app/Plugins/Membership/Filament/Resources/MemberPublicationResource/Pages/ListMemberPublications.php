<?php

namespace App\Plugins\Membership\Filament\Resources\MemberPublicationResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberPublicationResource;
use App\Plugins\Membership\Models\MemberPublication;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListMemberPublications extends ListRecords
{
    protected static string $resource = MemberPublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Add an update')];
    }

    /** Waiting for review first: the editorial team's queue */
    public function getTabs(): array
    {
        $count = fn (string $status) => MemberPublication::where('status', $status)->count();

        return [
            'pending' => Tab::make('Waiting for review')->badge($count('pending') ?: null)->modifyQueryUsing(fn ($query) => $query->where('status', 'pending')),
            'approved' => Tab::make('Published')->modifyQueryUsing(fn ($query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Not published')->modifyQueryUsing(fn ($query) => $query->where('status', 'rejected')),
            'all' => Tab::make('All'),
        ];
    }
}
