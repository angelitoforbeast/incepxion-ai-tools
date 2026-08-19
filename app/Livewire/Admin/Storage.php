<?php

namespace App\Livewire\Admin;

use App\Models\FromJnt;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Storage extends Component
{
    private function human($bytes): string
    {
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $bytes >= 100 ? 0 : 1).' '.$units[$i];
    }

    private function dirSize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }
        $size = 0;
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            // best-effort
        }

        return $size;
    }

    public function render()
    {
        $root  = base_path();
        $total = (float) (@disk_total_space($root) ?: 0);
        $free  = (float) (@disk_free_space($root) ?: 0);
        $used  = max(0, $total - $free);
        $percent = $total > 0 ? round($used / $total * 100, 1) : 0;

        $storageApp = $this->dirSize(storage_path('app'));

        $dbMb = null;
        if (DB::getDriverName() === 'mysql') {
            try {
                $r = DB::selectOne('SELECT ROUND(SUM(data_length+index_length)/1024/1024,1) AS mb FROM information_schema.tables WHERE table_schema = DATABASE()');
                $dbMb = $r->mb ?? null;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return view('livewire.admin.storage', [
            'activeTab'   => 'admin.storage',
            'totalH'      => $this->human($total),
            'usedH'       => $this->human($used),
            'freeH'       => $this->human($free),
            'percent'     => $percent,
            'storageAppH' => $this->human($storageApp),
            'fromJnts'    => FromJnt::count(),
            'dbMb'        => $dbMb,
        ]);
    }
}
