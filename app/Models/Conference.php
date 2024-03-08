<?php

namespace App\Models;

use App\Enums\Region;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Conference extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'region' => Region::class,
        'venue_id' => 'integer',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class);
    }

    public function talks(): BelongsToMany
    {
        return $this->belongsToMany(Talk::class);
    }

    public static function getForm(): array
    {
        return [
            Section::make('Conference Details')
               ->description('Provide some basic information about the conference.')
                ->collapsible()
                ->schema(
                    [TextInput::make('name')
                        ->columnSpanFull()
                        ->label('Conference Name')
                        ->default('My Conference')
                        ->required()
                        ->maxLength(60),
                        MarkdownEditor::make('description')
                            ->columnSpanFull()
                            ->required(),
                        DateTimePicker::make('start_date')
                            ->native(false)
                            ->required(),
                        DateTimePicker::make('end_date')
                            ->native(false)
                            ->required(),
                        Forms\Components\Fieldset::make('Status')
                        ->columns(1)
                        ->schema([
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                    'archived' => 'Archived'
                                ])
                                ->required(),
                            Toggle::make('is_published')
                                ->default(true),
                        ])
                    ]
                ),
            Section::make('Location Details')
                ->schema(
                    [
                        Select::make('region')
                            ->live()
                            ->enum(Region::class)
                            ->options(Region::class),
                        Select::make('venue_id')
                            ->searchable()
                            ->preload()
                            ->editOptionForm(Venue::getForm())
                            ->createOptionForm(Venue::getForm())
                            ->relationship('venue', 'name',
                                modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                    return $query->where('region', $get('region'));
                                }),
                    ]
                ),
            Actions::make([
                Action::make('star')
                    ->label('Fill with Factory Data')
                    ->icon('heroicon-m-star')
                    ->visible(function (string $operation) {
                        if($operation !== 'create') {
                            return false;
                        }
                        if (!app()->environment('local')){
                            return false;
                        }
                        return true;
                    })
                    ->requiresConfirmation()
                    ->action(function ($livewire) {
                        $data = Conference::factory()->make()->toArray();
                        $livewire->form->fill($data);
                    }),
            ]),
//            CheckboxList::make('speakers')
//                ->relationship('speakers', 'name')
//                ->options(
//                    Speaker::all()->pluck('name', 'id')
//                )
//                ->required(),
        ];

    }
}
