<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Filament\Actions\Action;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan Sistem';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([

                        // ── Baris 1: Informasi Akun (full width) ──────────
                        Section::make('Informasi Akun')
                            ->description('Data identitas dan akses login pengguna.')
                            ->icon(Heroicon::OutlinedUser)
                            ->columns(2)
                            ->columnSpan(12)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label('Email / Username')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->required(fn(string $operation): bool => $operation === 'create')
                                    ->rule(Password::defaults())
                                    ->helperText('Kosongkan jika tidak ingin mengubah password.'),

                                TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn(string $operation): bool => $operation === 'create')
                                    ->same('password')
                                    ->helperText('Harus sama dengan password di atas.'),

                                TextInput::make('private_key')
                                    ->label('Private Key')
                                    ->unique(User::class, 'private_key', ignoreRecord: true)
                                    ->dehydrated(true)
                                    ->readonly()
                                    ->maxLength(255),
                                TextInput::make('public_key')
                                    ->label('Public Key')
                                    ->unique(User::class, 'public_key', ignoreRecord: true)
                                    ->dehydrated(true)
                                    ->readonly()
                                    ->maxLength(255),
                            ]),

                        // ── Baris 2 Kiri: Departemen & Unit ───────────────
                        Section::make('Departemen & Unit')
                            ->description('Tentukan lokasi kerja pengguna dalam organisasi.')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->columns(2)
                            ->columnSpan(7)
                            ->schema([
                                Select::make('department_id')
                                    ->label('Departemen')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('unit_id', null)),

                                Select::make('unit_id')
                                    ->label('Unit Kerja')
                                    ->options(function ($get) {
                                        $departmentId = $get('department_id');
                                        if (!$departmentId) {
                                            return [];
                                        }
                                        return Unit::where('department_id', $departmentId)
                                            ->orderBy('name')
                                            ->orWhereNull('department_id')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->disabled(fn($get) => !$get('department_id'))
                                    ->helperText(fn($get) => !$get('department_id')
                                        ? 'Pilih departemen dahulu.'
                                        : null)
                                    ->live(),
                            ]),

                        // ── Baris 2 Kanan: Role & Hak Akses ───────────────
                        Section::make('Role & Hak Akses')
                            ->description('Hak akses pengguna.')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->columnSpan(5)
                            ->schema([
                                Select::make('roles')
                                    ->label('Role')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->helperText('Pilih minimal satu role.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedEnvelope),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->color(fn(string $state): string => match ($state) {
                        'administrator'    => 'danger',
                        'admin_gudang'     => 'warning',
                        'manager'          => 'info',
                        'manager_keuangan' => 'success',
                        'manager_jangum' => 'success',
                        'staf_unit'        => 'gray',
                        'direktur'         => 'primary',
                        default            => 'gray',
                    }),

                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('unit.name')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('roles')
                    ->label('Filter Role')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('department_id')
                    ->label('Filter Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('4xl'),
                DeleteAction::make()
                    ->requiresConfirmation(),
                Action::make('generateKeys')
                ->label('Aktivasi TTD')
                ->icon('heroicon-o-key')
                ->color('success')
                // Tombol ini hanya muncul jika user tersebut adalah Administrator
                // Sesuaikan logika 'role' di bawah dengan struktur aplikasi kamu
                ->visible(function () {
                        return auth()->user()?->hasRole('administrator');
                    }) 
                // Minta konfirmasi sebelum generate kunci
                ->requiresConfirmation()
                ->modalHeading('Aktivasi TTD Digital')
                ->modalDescription('Apakah Anda yakin ingin membuat kunci TTD Digital untuk user ini? Kunci lama (jika ada) akan digantikan.')
                ->action(function ($record) {

                    $opensslConfigPath = "/etc/ssl/openssl.cnf";

                    // Alternatif backup jika dipindah ke Laragon standar
                    if (!file_exists($opensslConfigPath)) {
                        $opensslConfigPath = "D:/laragon/bin/php/php-" . PHP_VERSION . "-Win32-vs17-x64/extras/ssl/openssl.cnf";
                    }

                    // LOGIKA KRIPTOGRAFI OPENSSL
                    $config = array(
                        "digest_alg" => "sha256",
                        "private_key_bits" => 2048,
                        "private_key_type" => OPENSSL_KEYTYPE_RSA,
                        "config" => $opensslConfigPath,
                    );

                    // PENTING: Seringkali di Windows/Laragon, OpenSSL butuh ditarik file config-nya secara manual jika throws error
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        // Laragon biasanya menyimpan openssl.cnf di folder apache atau postgresql (seperti di phpinfo kamu)
                        // Kita arahkan secara dinamis jika sistem default-nya bermasalah
                        $config["config"] = "D:/laragon/bin/php/php-" . PHP_VERSION . "-Win32-vs17-x64/extras/ssl/openssl.cnf";
                        
                        // Backup alternatif jika file di folder PHP tidak ketemu
                        if (!file_exists($config["config"])) {
                            $config["config"] = "C:/Program Files/PostgreSQL/psqlODBC/etc/openssl.cnf"; 
                        }
                    }

                    // Generate sepasang kunci baru
                    $res = openssl_pkey_new($config);

                    // JIKA MASIH GAGAL, gunakan fallback tanpa array config (PHP akan pakai settingan default internal)
                    if (!$res) {
                        throw new \Exception("Gagal inisialisasi OpenSSL. Pastikan file config tersedia di: " . $opensslConfigPath . ". Error: " . openssl_error_string());
                    }

                    // 2. Ekstrak Private Key
                    openssl_pkey_export($res, $privateKey, null, $config);

                    // 3. Ekstrak Public Key
                    $publicKeyDetails = openssl_pkey_get_details($res);
                    $publicKey = $publicKeyDetails["key"];

                    // 4. Simpan ke database dengan enkripsi aman pada Private Key
                    $record->update([
                        'private_key' => Crypt::encryptString($privateKey),
                        'public_key' => $publicKey
                    ]);

                    // 5. Tampilkan notifikasi sukses di Filament
                    Notification::make()
                        ->title('Sukses!')
                        ->body('Kunci TTD Digital untuk ' . $record->name . ' berhasil diaktifkan.')
                        ->success()
                        ->send();
                }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
