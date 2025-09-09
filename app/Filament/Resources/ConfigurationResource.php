<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationResource\Pages;
use App\Filament\Resources\ConfigurationResource\RelationManagers;
use App\Models\Configuration;
use App\Services\Configuration\ConfigurationFormBuilder;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class ConfigurationResource extends Resource
{
  protected static ?string $model = Configuration::class;

  protected static ?string $navigationIcon = 'heroicon-o-cog';
  protected static ?string $navigationGroup = 'Configuración';
  protected static ?int $navigationSort = 3;
  protected static $title = 'Empresa';
  protected static ?string $label = 'empresa';
  protected static ?string $pluralLabel = 'empresa';
  protected static ?string $slug = 'ajustes';


  /**
   * Defines the form schema for the Configuration resource.
   *
   * @param Form $form
   * @return Form
   */
  public static function form(Form $form): Form
  {
    return ConfigurationFormBuilder::form($form);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\EditConfiguration::route('/'),
    ];
  }
}
