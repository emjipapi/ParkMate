<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UnknownRfidLog as UnknownTag;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class UnknownTagsComponent extends Component
{
    use WithPagination;

    public $perPage = 15;
public $perPageOptions = [15, 25, 50, 100];
    protected $paginationTheme = 'bootstrap';

    // simple search by tag
    public $search = '';
public $startDate = null;
public $endDate = null;

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
$query = UnknownTag::query();

if (!empty($this->search)) {
    $s = trim($this->search);
    $query->where('rfid_tag', 'like', "%{$s}%");
}

// date filters
$query->when($this->startDate, fn($q) =>
    $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay())
);
$query->when($this->endDate, fn($q) =>
    $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay())
);

$tags = $query->orderBy('created_at', 'desc')
              ->paginate($this->perPage);

        return view('livewire.admin.unknown-tags-component', [
            'unknownTags' => $tags,
        ]);
    }

    /**
     * Assign an unknown tag to a user.
     *
     * This writes the rfid_tag value into the user's `epc` column
     * and removes all unknown log rows for that tag so it no longer shows
     * in the Unknown Tags list.
     *
     * NOTE: adjust the user's column name if you use something other than `epc`.
     */
    public function assignToUser(int $tagId, int $userId)
    {
        $tag = UnknownTag::find($tagId);
        if (! $tag) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Unknown tag not found.']);
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'User not found.']);
            return;
        }

        try {
            // write the tag into user's epc column (change field name if needed)
            $user->epc = $tag->rfid_tag;
            $user->save();

            // remove all unknown log entries for this tag (we assume assignment resolves them)
            UnknownTag::where('rfid_tag', $tag->rfid_tag)->delete();

            $this->resetPage();
            $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Tag assigned to user and unknown log(s) removed.']);
        } catch (\Throwable $ex) {
            Log::error('Assign unknown tag failed: '.$ex->getMessage());
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Failed to assign tag.']);
        }
    }

    /**
     * Ignore the tag (delete the unknown log rows).
     * Use with caution — this permanently removes the audit rows for the tag.
     */
    public function ignore(int $tagId)
    {
        $tag = UnknownTag::find($tagId);
        if (! $tag) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Unknown tag not found.']);
            return;
        }

        try {
            UnknownTag::where('rfid_tag', $tag->rfid_tag)->delete();

            $this->resetPage();
            $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Tag ignored (logs removed).']);
        } catch (\Throwable $ex) {
            Log::error('Ignore unknown tag failed: '.$ex->getMessage());
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Failed to ignore tag.']);
        }
    }
}
