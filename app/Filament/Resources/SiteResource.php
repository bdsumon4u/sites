<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Jobs\ForceUpdateSite;
use App\Jobs\UpdateSite;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $serverForm = [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('ip')
                        ->label('IP')
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required(),
                    Forms\Components\TextInput::make('token')
                        ->label('Token')
                        ->required(fn (string $operation): bool => $operation === 'createOption')
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    Forms\Components\TextInput::make('username')
                        ->label('Username')
                        ->required(),
                    Forms\Components\TextInput::make('endpoint')
                        ->label('Endpoint')
                        ->required()
                        ->columnSpanFull(),
                ]),
        ];

        return $form
            ->schema([
                Forms\Components\Section::make('SSH')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('key_name')
                                    ->label('Key Name')
                                    ->hintColor(Color::Red)
                                    ->formatStateUsing(fn () => 'GACD')
                                    ->hint(new HtmlString('Must be <strong>GACD</strong>'))
                                    ->hintIcon('heroicon-o-exclamation-circle'),
                                Forms\Components\TextInput::make('private_key')
                                    ->label('Private Key')
                                    ->hint('Empty'),
                                Forms\Components\TextInput::make('passphrase')
                                    ->label('Passphrase')
                                    ->hint('Empty'),
                            ]),
                        Forms\Components\Textarea::make('public_key')
                            ->formatStateUsing(fn () => Storage::drive('local')->get('GACD.pub'))
                            ->label('Public Key')
                            ->rows(8),
                    ])
                    ->disabled()
                    ->collapsible(),
                Forms\Components\Group::make([
                    Forms\Components\Select::make('copy_from')
                        ->label('Copy From')
                        ->options(fn () => Site::query()->pluck('domain', 'id')->toArray())
                        ->searchable()
                        ->live(),
                    Forms\Components\TextInput::make('site_name')
                        ->label('Site Name')
                        ->required(fn (Forms\Get $get) => $get('copy_from'))
                        ->disabled(fn (Forms\Get $get) => ! $get('copy_from')),
                    Forms\Components\Section::make('cPanel')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('server_id')
                                        ->relationship('server', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpanFull()
                                        ->createOptionForm($serverForm)
                                        ->editOptionForm($serverForm),
                                    Forms\Components\TextInput::make('service_id')
                                        ->label('Service ID')
                                        ->integer(),
                                    Forms\Components\TextInput::make('uname')
                                        ->label('Username')
                                        ->alphaDash()
                                        ->required(),
                                    Forms\Components\TextInput::make('domain')
                                        ->label('Domain')
                                        ->required()
                                        ->live(true)
                                        ->unique(ignoreRecord: true)
                                        // ->rule(fn ($state) => filter_var($state, FILTER_VALIDATE_DOMAIN))
                                        ->afterStateUpdated(function ($set, $state) {
                                            $set('directory', substr_count($state, '.') == 2 ? $state : 'public_html');
                                            $set('mail_user', 'support@'.$state);
                                        }),
                                    Forms\Components\TextInput::make('directory')
                                        // ->rule(fn ($state) => preg_match('/^[a-zA-Z0-9.]+$/', $state))
                                        ->label('Directory')
                                        ->required(),
                                ]),
                        ]),
                ])
                    ->columns(2)
                    ->columnSpan(2),
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Mail')
                        ->schema([
                            Forms\Components\TextInput::make('mail_user')
                                ->label('Username')
                                ->email()
                                ->required(),
                            Forms\Components\TextInput::make('mail_pass')
                                ->label('Password')
                                ->required()
                                ->default(Str::random(10))
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('generate')
                                        ->icon('heroicon-o-arrow-path')
                                        ->action(fn ($component) => $component->state(Str::random(10)))
                                ),
                        ])
                        ->columns(2)
                        ->columnSpan(1),
                    Forms\Components\Section::make('Database')
                        ->schema([
                            Forms\Components\TextInput::make('db_name')
                                ->dehydrateStateUsing(fn ($get, $state) => $get('uname').'_'.$state)
                                ->label('Name')
                                ->alphaDash()
                                ->default('maindb')
                                ->required()
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('generate')
                                        ->icon('heroicon-o-arrow-path')
                                        ->action(fn ($component) => $component->state(Str::random(6)))
                                ),
                            Forms\Components\TextInput::make('db_user')
                                ->dehydrateStateUsing(fn ($get, $state) => $get('uname').'_'.$state)
                                ->label('Username')
                                ->alphaDash()
                                ->default('cyber32')
                                ->required()
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('generate')
                                        ->icon('heroicon-o-arrow-path')
                                        ->action(fn ($component) => $component->state(Str::random(6)))
                                ),
                            Forms\Components\TextInput::make('db_pass')
                                ->label('Password')
                                ->default(Str::random(10))
                                ->required()
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('generate')
                                        ->icon('heroicon-o-arrow-path')
                                        ->action(fn ($component) => $component->state(Str::random(10)))
                                ),
                        ])
                        ->columns(3)
                        ->columnSpan(1),
                ])
                    ->disabled(fn (Forms\Get $get) => ! $get('copy_from'))
                    ->columnSpan(3),
            ])
            ->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_id')
                    ->label('Service ID')
                    ->searchable()
                    ->sortable()
                    ->disabled(),
                Tables\Columns\TextColumn::make('uname')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('domain')
                    ->url(fn ($record) => 'https://'.$record->domain)
                    ->label('Domain')
                    ->openUrlInNewTab()
                    ->iconPosition('after')
                    ->icon('heroicon-o-link')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->searchable()
                    ->sortable()
                    ->date() // ->dateTime()
                    ->tooltip(fn ($record) => $record->updated_at?->format(
                        Table::$defaultTimeDisplayFormat,
                    )),
                Tables\Columns\TextColumn::make('time')
                    ->label('Update Time')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->getStateUsing(fn ($record) => $record->updated_at?->format(
                        Table::$defaultTimeDisplayFormat,
                    )),
                Tables\Columns\IconColumn::make('status')
                    ->tooltip(fn ($state) => $state)
                    ->icon(fn ($state) => match ($state) {
                        'Active' => 'heroicon-o-check-circle',
                        'Paused' => 'heroicon-o-pause-circle',
                        'Failed' => 'heroicon-o-exclamation-circle',
                        'Outage' => 'heroicon-o-arrow-down-circle',
                        default => 'heroicon-o-arrow-path-rounded-square',
                    })
                    ->color(fn ($state) => match ($state) {
                        'Active' => 'success',
                        'Paused' => 'danger',
                        'Failed' => 'danger',
                        'Outage' => 'warning',
                        default => 'neutral',
                    })
                    ->disabledClick(function ($state, $record) {
                        if ($state == 'Failed' && ! $record->updated_at) {
                            return true; // Deployment failed.
                        }

                        return $state == 'Processing'; // Deployment is in progress.
                    })
                    ->action(function ($record) {
                        $record->update(['status' => match ($record->status) {
                            'Active' => 'Paused',
                            default => 'Processing',
                        }]);

                        UpdateSite::dispatchIf($record->status == 'Processing', $record->toArray());

                        return Notification::make()
                            ->title($record->status)
                            ->success()
                            ->send();
                    })
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('force_update')
                    ->label('Force Update')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color(Color::Blue)
                    ->action(function ($record) {
                        ForceUpdateSite::dispatch($record->toArray());

                        return Notification::make()
                            ->title('Site is being updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('update')
                        ->label('Update selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => 'Processing']);

                                UpdateSite::dispatchIf($record->status == 'Processing', $record->toArray());
                            }

                            $message = count($records) == 1 ? 'Site is being updated' : 'Sites are being updated';

                            return Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            // 'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
