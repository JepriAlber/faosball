 <div x-data="{ open: false }" class="relative">

     <button type="button" @click="open=!open" class="btn btn-secondary">
         <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
             <path d="M10 4.5V4.51M10 10V10.01M10 15.5V15.51" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" />
         </svg>

     </button>

     <div x-show="open" x-cloak @click.away="open=false" class="dropdown-menu right-0">

         @if ($user)

             {{-- Edit Account --}}
             <a href="{{ route($routeEdit, $model) }}" class="dropdown-item">
                 Edit Account
             </a>

             {{-- Reset Password --}}
             <button type="button" class="dropdown-item-danger w-full text-left"
                 @click="$dispatch('reset-password-confirm',{
                    action:'{{ route($routePassword, $model) }}',
                    name:'{{ $model->name }}'
                })">
                 Reset Password
             </button>

             <div class="dropdown-divider"></div>

             {{-- Status Toggle --}}
             @if ($user->status)
                 <button type="button" class="dropdown-item-danger w-full text-left"
                     @click="$dispatch('status-confirm',{
                        action:'{{ route($routeStatus, $model) }}',
                        name:'{{ $model->name }}',
                        status:true
                    })">
                     Disable Account
                 </button>
             @else
                 <button type="button" class="dropdown-item-success w-full text-left"
                     @click="$dispatch('status-confirm',{
                        action:'{{ route($routeStatus, $model) }}',
                        name:'{{ $model->name }}',
                        status:false
                    })">
                     Enable Account
                 </button>
             @endif
         @else
             <a href="{{ route($routeCreate, $model) }}" class="dropdown-item">
                 Buat Account
             </a>

         @endif

     </div>

 </div>
