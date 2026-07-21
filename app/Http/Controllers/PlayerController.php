<?php

namespace App\Http\Controllers;

use App\Http\Requests\Players\StorePlayerRequest;
use App\Http\Requests\Players\UpdatePlayerRequest;
use App\Models\Academy;
use App\Models\Player;
use App\Services\AcademyService;
use App\Services\PlayerCategoryService;
use App\Services\PlayerPositionService;
use App\Services\PlayerService;
use App\Services\PlayerTypeService;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    protected PlayerService $playerService;
    protected AcademyService $academyService;
    protected PlayerTypeService $playerTypeService;
    protected PlayerCategoryService $playerCategoryService;
    protected PlayerPositionService $playerPositionService;

    public function __construct(
        PlayerService $playerService,
        AcademyService $academyService,
        PlayerTypeService $playerTypeService,
        PlayerCategoryService $playerCategoryService,
        PlayerPositionService $playerPositionService
    ) {
        $this->playerService = $playerService;
        $this->academyService = $academyService;
        $this->playerTypeService = $playerTypeService;
        $this->playerCategoryService = $playerCategoryService;
        $this->playerPositionService = $playerPositionService;
    }

    public function index(Request $request)
    {
        // array_filter membuang value kosong (mis. "Semua Type" -> '') supaya
        // tidak ikut nyangkut di query string / query filter.
        $filters = array_filter($request->only([
            'search', 'status', 'id_player_type', 'id_player_category', 'gender', 'sort',
        ]));

        return view('players.index',[
            'title'=>__('Players'),
            'breadcrumb'=>[
                [
                    'label'=>__('Players')
                ]
            ],
            'players' => $this->playerService->paginate($filters),
            'statusCounts' => $this->playerService->statusCounts($filters),
            'filters' => $filters,
            // Opsi untuk dropdown filter (bukan form create) -- Super Admin
            // melihat seluruh academy, user academy cukup miliknya sendiri.
            'playerTypeOptions' => $this->playerTypeService->selectable(
                $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
            ),
            'playerCategoryOptions' => $this->playerCategoryService->selectable(
                $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
            ),
        ]);
    }

    public function create()
    {
        return view('players.create',[
            'title'=>__('Create Player'),
            'breadcrumb'=>[
                [
                    'label'=>__('Players'),
                    'url'=>route('players.index')
                ],
                [
                    'label'=>__('Create')
                ]
            ],
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
            'academies'=>$this->academyService->isSuperAdmin()
                ? Academy::orderBy('name')->get()
                : collect(),
            // Super Admin: seluruh academy (difilter di sisi Alpine mengikuti academy
            // yang dipilih). User academy: cukup type miliknya sendiri.
            'playerTypes' => $this->playerTypeService->selectable(
                $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
            ),
            'playerCategories' => $this->playerCategoryService->selectable(
                $this->academyService->isSuperAdmin() ? null : $this->academyService->currentId()
            ),
            'playerPositions' => $this->playerPositionService->selectable(),
        ]);
    }

    public function store(StorePlayerRequest $request)
    {
        try {

            $this->playerService->create(
                $request->validated()
            );

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    __('Player berhasil dibuat.')
                );

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal membuat player'));
        }
    }

    public function show(Player $player)
    {
        $player->load([
            'academy',
            'playerType',
            'playerCategory',
            'primaryPosition',
            'secondaryPosition',
            'user.roles'
        ]);

        return view('players.show',[
            'title'=>__('Detail Player'),
            'breadcrumb'=>[
                [
                    'label'=>__('Players'),
                    'url'=>route('players.index')
                ],
                [
                    'label'=>__('Detail Player')
                ]
            ],
            'player'=>$player
        ]);
    }

    public function edit(Player $player)
    {
        return view('players.edit',[
            'title'=>__('Edit Player'),
            'breadcrumb'=>[
                [
                    'label'=>__('Players'),
                    'url'=>route('players.index')
                ],
                [
                    'label'=>__('Edit')
                ]
            ],
            'player'=>$player,
            'isSuperAdmin'=>$this->academyService->isSuperAdmin(),
            // Academy player tidak berubah, jadi cukup type milik academy player itu.
            // includeId dipakai supaya type yang sudah dinonaktifkan tetap muncul kalau
            // player ini memang sedang memakainya.
            'playerTypes' => $this->playerTypeService->selectable(
                $player->id_academy,
                $player->id_player_type
            ),
            'playerCategories' => $this->playerCategoryService->selectable(
                $player->id_academy,
                $player->id_player_category
            ),
            // Saran kategori untuk player ini. Berguna terutama untuk player lama yang
            // id_player_category-nya masih NULL.
            'suggestedCategory' => $this->playerCategoryService->suggestFor(
                $player->birth_date,
                $player->id_academy
            ),
            // Dua argumen supaya posisi yang sudah dinonaktifkan tapi masih
            // dipakai player ini tetap muncul di dropdown.
            'playerPositions' => $this->playerPositionService->selectable(
                $player->id_primary_position,
                $player->id_secondary_position
            ),
        ]);
    }

    public function update(UpdatePlayerRequest $request, Player $player)
    {
        try {

            $this->playerService->update(
                $player,
                $request->validated()
            );

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    __('Player berhasil diperbarui.')
                );

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal memperbarui player'));
        }
    }


    public function destroy(Player $player)
    {
        try {

            $this->playerService->delete(
                $player
            );

            return redirect()
                ->route('players.index')
                ->with(
                    'success',
                    __('Player berhasil dihapus.')
                );

        } catch (\Exception $e) {

            return $this->handleException($e, __('Gagal menghapus player'), 'players.index');
        }
    }

}