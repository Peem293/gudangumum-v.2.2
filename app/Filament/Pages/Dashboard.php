<?php

namespace App\Filament\Pages;

use App\Models\Request;
use App\Models\RequestDetail;
use App\Models\PurchaseOrder;
use App\Models\Item;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Page
{
    protected static ?string $slug = 'dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected string $view = 'filament.pages.dashboard';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function canView(): bool
    {
        return Auth::check();
    }

    // Scoping request query based on role
    protected function getScopedRequestQuery()
    {
        $user = Auth::user();
        $query = Request::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Administrator, Direktur, Admin Gudang, Manager Keuangan: global
        if ($user->hasRole(['administrator', 'direktur', 'admin_gudang', 'manager_keuangan'])) {
            return $query;
        }

        // Manager biasa & Manager Penunjang Umum: lihat departemen sendiri
        if ($user->hasRole('manager')) {
            return $query->where('department_id', $user->department_id);
        }

        // Staf Unit: unit sendiri
        if ($user->hasRole('staf_unit')) {
            return $query->where('unit_id', $user->unit_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canAccessPurchasing(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasRole(['administrator', 'direktur', 'admin_gudang', 'manager_keuangan'])
            || $user->isPurchasingManager();
    }

    public function canAccessLowStock(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasRole(['administrator', 'admin_gudang', 'direktur', 'manager_keuangan']);
    }

    /**
     * Get counts for summary cards based on user permissions.
     */
    public function getSummaryData(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $requestQuery = $this->getScopedRequestQuery();
        
        $requestCompleted = (clone $requestQuery)->where('status', 'completed')->count();
        $requestOnProcess = (clone $requestQuery)->whereIn('status', ['pending', 'approved'])->count();
        $requestRejected = (clone $requestQuery)->where('status', 'rejected')->count();
        
        $hasPurchasing = $this->canAccessPurchasing();
        
        $purchaseCompleted = 0;
        $purchaseOnProcess = 0;
        $purchaseDraft = 0;
        
        if ($hasPurchasing) {
            $purchaseCompleted = PurchaseOrder::where('status', 'received')->count();
            $purchaseOnProcess = PurchaseOrder::where('status', 'ordered')->count();
            $purchaseDraft = PurchaseOrder::where('status', 'draft')->count();
        }
        
        return [
            'requestCompleted' => $requestCompleted,
            'requestOnProcess' => $requestOnProcess,
            'requestRejected'  => $requestRejected,
            'hasPurchasing'    => $hasPurchasing,
            'purchaseCompleted' => $purchaseCompleted,
            'purchaseOnProcess' => $purchaseOnProcess,
            'purchaseDraft'     => $purchaseDraft,
        ];
    }

    /**
     * Get monthly completed transaction trends for the past 6 months.
     */
    public function getChartData(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        
        $requestQuery = $this->getScopedRequestQuery();
        $hasPurchasing = $this->canAccessPurchasing();
        
        $labels = [];
        $requestTrend = [];
        $purchaseTrend = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->translatedFormat('F');
            $labels[] = $monthName;
            
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $requestTrend[] = (clone $requestQuery)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();
            
            if ($hasPurchasing) {
                $purchaseTrend[] = PurchaseOrder::where('status', 'received')
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();
            } else {
                $purchaseTrend[] = 0;
            }
        }
        
        return [
            'labels' => $labels,
            'requestTrend' => $requestTrend,
            'purchaseTrend' => $purchaseTrend,
            'hasPurchasing' => $hasPurchasing,
        ];
    }

    /**
     * Get distribution chart data based on role.
     */
    public function getDistributionData(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        
        $labels = [];
        $data = [];
        $title = '';
        
        if ($user->hasRole(['administrator', 'direktur', 'manager_keuangan', 'admin_gudang'])) {
            $title = 'Distribusi Permintaan per Departemen';
            $results = Request::where('status', 'completed')
                ->select('department_id', DB::raw('count(*) as total'))
                ->groupBy('department_id')
                ->with('department')
                ->get();
            foreach ($results as $res) {
                $labels[] = $res->department->name ?? 'Tanpa Departemen';
                $data[] = (int) $res->total;
            }
        } elseif ($user->hasRole('manager')) {
            $title = 'Distribusi Permintaan per Unit Kerja';
            $results = Request::where('status', 'completed')
                ->where('department_id', $user->department_id)
                ->select('unit_id', DB::raw('count(*) as total'))
                ->groupBy('unit_id')
                ->with('unit')
                ->get();
            foreach ($results as $res) {
                $labels[] = $res->unit->name ?? 'Tanpa Unit';
                $data[] = (int) $res->total;
            }
        } else {
            $title = 'Top 5 Barang Paling Sering Diminta';
            $results = RequestDetail::whereHas('request', function ($q) use ($user) {
                $q->where('status', 'completed')
                    ->where('unit_id', $user->unit_id);
            })
                ->select('item_id', DB::raw('sum(qty_requested) as total'))
                ->groupBy('item_id')
                ->with('item')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
            foreach ($results as $res) {
                $labels[] = $res->item->name ?? 'Barang Tanpa Nama';
                $data[] = (int) $res->total;
            }
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'title' => $title,
        ];
    }

    /**
     * Get items with low stock.
     */
    public function getLowStockItems(): \Illuminate\Database\Eloquent\Collection
    {
        return Item::where('stock', '<=', 10)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
    }
}
