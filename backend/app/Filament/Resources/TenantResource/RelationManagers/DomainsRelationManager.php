<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Filament\Resources\TenantResource;
use App\Services\FlyCertificates;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * The site's domains. Adding one requests its certificate from Fly and
 * removing one removes it (App\Support\SyncDomainCertificate); changing a
 * domain is adding the new one, then removing the old one.
 */
class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Domains';

    public function isReadOnly(): bool
    {
        return ! TenantResource::canManage();
    }

    public function table(Table $table): Table
    {
        $certificates = app(FlyCertificates::class);

        return $table
            ->recordTitleAttribute('domain')
            ->description($certificates->enabled()
                ? 'Each domain gets an HTTPS certificate automatically once its DNS points to the platform. Open "DNS records" to see what to set at the domain\'s registrar.'
                : 'Certificates are only managed on Fly (FLY_API_TOKEN is not set here).')
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->url(fn (Model $record) => "https://{$record->domain}", shouldOpenInNewTab: true),
                Tables\Columns\TextColumn::make('certificate')
                    ->label('Certificate')
                    ->visible($certificates->enabled())
                    ->badge()
                    ->state(fn (Model $record) => FlyCertificates::describe($this->certificate($record->domain))[0])
                    ->color(fn (Model $record) => FlyCertificates::describe($this->certificate($record->domain))[1]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add domain')
                    ->modalHeading('Add domain')
                    ->form([TenantResource::domainField('domain'), TenantResource::domainConfirmField('domain')])
                    ->successNotificationTitle('Domain added. Set its DNS records so the certificate can be issued.'),
            ])
            ->actions([
                Tables\Actions\Action::make('dns')
                    ->label('DNS records')
                    ->icon('heroicon-o-globe-alt')
                    ->visible($certificates->enabled())
                    ->modalHeading(fn (Model $record) => "DNS records for {$record->domain}")
                    ->modalContent(fn (Model $record) => $this->dnsInstructions($record->domain))
                    ->modalSubmitActionLabel('Check again')
                    ->action(function (Model $record) use ($certificates) {
                        $certificate = $this->certificate($record->domain, fresh: true);
                        [$label, $colour] = FlyCertificates::describe($certificate);
                        Notification::make()->title("{$record->domain}: {$label}")->color($colour)
                            ->body($certificate['validation_errors'][0]['message'] ?? null)->send();
                    }),
                Tables\Actions\Action::make('request')
                    ->label('Request certificate')
                    ->icon('heroicon-o-lock-closed')
                    ->visible(fn (Model $record) => $certificates->enabled() && TenantResource::canManage() && ! $this->certificate($record->domain))
                    ->action(function (Model $record) use ($certificates) {
                        try {
                            $certificates->add($record->domain);
                            Notification::make()->success()->title('Certificate requested')->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title('Fly refused the request')->body($e->getMessage())->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading(fn (Model $record) => "Remove {$record->domain}?")
                    ->modalDescription('The site stops answering on this domain and its certificate is removed.')
                    ->before(function (Model $record, Tables\Actions\DeleteAction $action) {
                        if ($this->getOwnerRecord()->domains()->count() <= 1) {
                            Notification::make()->danger()->title('A site needs at least one domain')
                                ->body('Add the new domain first, then remove this one.')->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    private function certificate(string $domain, bool $fresh = false): ?array
    {
        try {
            return app(FlyCertificates::class)->get($domain, $fresh);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function dnsInstructions(string $domain): HtmlString
    {
        $dns = $this->certificate($domain)['dns_requirements'] ?? null;
        if (! $dns) {
            return new HtmlString('<p style="font-size:.875rem">There is no certificate for this domain yet. Use "Request certificate" first.</p>');
        }

        $row = fn (string $type, string $name, string $value) => '<tr><td style="padding:.35rem .75rem .35rem 0;font-weight:600">'.e($type).'</td>'
            .'<td style="padding:.35rem .75rem .35rem 0;font-family:monospace">'.e($name).'</td>'
            .'<td style="padding:.35rem 0;font-family:monospace;word-break:break-all">'.e($value).'</td></tr>';

        // A/AAAA work for any name; a www. name can point at the app with a CNAME instead
        $cname = str_starts_with($domain, 'www.');
        $rows = $cname
            ? $row('CNAME', $domain, $dns['cname'])
            : collect($dns['a'] ?? [])->map(fn ($ip) => $row('A', $domain, $ip))->join('')
                .collect($dns['aaaa'] ?? [])->map(fn ($ip) => $row('AAAA', $domain, $ip))->join('');

        return new HtmlString(
            '<p style="font-size:.875rem;margin-bottom:.75rem">Add '.($cname ? 'this record' : 'these records').' at the registrar for <strong>'.e($domain).'</strong>, '
            .'removing any other A, AAAA or CNAME records for the same name. Some registrars want only the part before the main domain as the name (e.g. <code>@</code> or <code>www</code>). '
            .'The certificate is issued automatically once the records are live, usually within minutes.</p>'
            .'<table style="font-size:.875rem;width:100%"><thead><tr style="text-align:left;opacity:.7"><th>Type</th><th>Name</th><th>Value</th></tr></thead><tbody>'.$rows.'</tbody></table>'
        );
    }
}
