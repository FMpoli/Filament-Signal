# Tech Stack & Development Guidelines

## Core Frameworks
- **Laravel**: 12.x
- **FilamentPHP**: 4.x (Bleeding Edge / Custom)
- **PHP**: 8.3+

## Filament 4 Development Notes
**IMPORTANT**: This project uses Filament 4, which introduces several breaking changes compared to v3.

### 1. Resources & Forms
- The `form()` method signature has changed. It now uses `Schema` instead of `Form`.
  ```php
  // ❌ Incorrect (v3)
  public static function form(Form $form): Form
  
  // ✅ Correct (v4)
  use Filament\Schemas\Schema;
  public static function form(Schema $schema): Schema
  {
      return $schema->components([...]);
  }
  ```

### 2. Actions Namespace
- Table actions have been unified/moved. Do not use `Filament\Tables\Actions\Action`.
- Use the generic action class instead.
  ```php
  // ❌ Incorrect
  use Filament\Tables\Actions\Action;
  
  // ✅ Correct
  use Filament\Actions\Action;
  use Filament\Actions\EditAction;
  ```

### 3. Strict Typing
- Resource static properties now enforce strict typing (often Union Types).
- Prefer using static methods over properties to avoid type mismatches.
  ```php
  // ❌ Incorrect
  protected static ?string $navigationGroup = 'Settings';
  
  // ✅ Correct
  public static function getNavigationGroup(): ?string
  {
      return 'Settings';
  }
  ```
