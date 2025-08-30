<div class="flex min-h-0 h-full max-h-screen bg-white/50 dark:bg-gray-900/30 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
    <!-- Sidebar (hidden in minimal mode) -->
    <aside class="w-80 shrink-0 border-r border-gray-200 dark:border-gray-800 hidden md:flex flex-col min-h-0 text-left {{ $this->minimal ? 'md:hidden' : '' }}">
        <div class="p-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 sticky top-0 z-10"
             x-data="{ focus(){ this.$refs.chatSearch?.focus(); }, clear(){ $wire.set('q',''); }, isTyping:false }"
             @keydown.window.prevent.slash="if(document.activeElement.tagName!=='INPUT'&&document.activeElement.tagName!=='TEXTAREA'){ focus() }"
        >
            <div class="relative group">
                <input
                    x-ref="chatSearch"
                    type="text"
                    wire:model.debounce.300ms="q"
                    placeholder="Search chats…"
                    aria-label="Search chats"
                    x-on:keydown.escape.prevent="clear(); $el.blur()"
                    class="w-full text-sm rounded-xl px-3 pr-10 py-2 min-w-0
                           bg-white/95 dark:bg-gray-900/95
                           placeholder:text-gray-400 dark:placeholder:text-gray-500
                           text-gray-800 dark:text-gray-100
                           border border-gray-200 dark:border-gray-800 shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400
                           transition"
                />
                <svg wire:loading wire:target="q" class="absolute right-3 top-2.5 size-4 text-gray-400 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path></svg>
            </div>
        </div>
        <div class="flex-1 overflow-auto">
            @forelse($conversations as $conv)
                @php
                    $names = collect($conv->participants)
                        ->filter(fn($p) => ($p->participant?->id ?? null) !== (auth()->user()->id ?? auth('customer')->id()))
                        ->map(fn($p) => $p->participant?->name)
                        ->filter()
                        ->values();
                    $title = $conv->name ?: $names->implode(', ');
                    $initial = mb_substr($title, 0, 1);
                    $preview = optional($conv->messages->first())->body ?: '—';
                    $isGroup = $conv->type === 'group';
                @endphp
                <button wire:click="$set('activeConversationId', {{ $conv->id }})" class="w-full text-left px-3 py-2 hover:bg-primary-50 dark:hover:bg-gray-800/50 {{ $activeConversation?->id === $conv->id ? 'bg-primary-50 ring-1 ring-primary-200 dark:bg-gray-800/60 dark:ring-0' : '' }}"
                        x-data="{count:null, subscribed:false, subscribe(){ if(!window.Echo||this.subscribed) return; this.subscribed=true; window.Echo.join('presence-conversations.'+@js($conv->id)).here(u=>this.count=u.length).joining(()=>this.count=(this.count||0)+1).leaving(()=>this.count=(this.count||1)-1) }}"
                        @mouseenter.once="subscribe()">
                    <div class="flex gap-3 items-start text-left">
                        <div class="relative h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 text-white flex items-center justify-center font-semibold shadow-sm ring-2 ring-white/70 dark:ring-gray-900">
                            {{ $initial }}
                            <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-900" :class="(count||0) > 0 ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                        </div>
                        <div class="min-w-0 flex-1 text-left">
                            <div class="flex flex-col items-start gap-1">
                                <div class="font-medium text-sm truncate w-full text-left">{{ $title }}</div>
                                <div class="flex items-center gap-1 w-full">
                                    <span class="text-gray-300">•</span>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 shrink-0">
                                        <span>{{ $conv->updated_at?->shortAbsoluteDiffForHumans() }}</span>
                                        <span x-show="count !== null" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800"><svg class="size-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg><span x-text="count"></span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 truncate text-left">{{ $preview }}</div>
                            @if($isGroup)
                                <div class="mt-0.5 text-[11px] text-gray-500 text-left">{{ ucfirst($conv->privacy) }}</div>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="p-4 text-sm text-gray-500 text-left">No conversations yet.</div>
            @endforelse
        </div>
        <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
            <button wire:click="$set('showNewChatModal', true)" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-primary-600 text-white text-sm font-medium hover:bg-primary-500">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5a.75.75 0 01.75.75V11h5.75a.75.75 0 010 1.5H12.75v5.75a.75.75 0 01-1.5 0V12.5H5.5a.75.75 0 010-1.5h5.75V5.25A.75.75 0 0112 4.5z"/></svg>
                <span>New Chat</span>
            </button>
        </div>
    </aside>

    <!-- Chat window -->
    <section class="flex-1 flex flex-col min-h-0" x-data="{
        channel: null,
        subscribe(convId){
            if (!window.Echo || !convId) return;
            if (this.channel) { try { window.Echo.leaveChannel(this.channel.name); } catch(e) {} }
                        this.channel = window.Echo.private('conversations.'+convId)
              .listen('.message.created', () => { $wire.$refresh(); })
              .listen('.message.updated', () => { $wire.$refresh(); })
              .listen('.message.deleted', () => { $wire.$refresh(); })
                            .listen('.reaction.toggled', () => { $wire.$refresh(); })
                            .listen('.conversation.terminated', () => { $wire.$refresh(); });
        }
    }" x-init="subscribe(@js($activeConversation?->id))" x-effect="subscribe(@js($activeConversation?->id))">
        <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40"
                         x-data="{
                                convId: @js($activeConversation?->id),
                                presence: null,
                                members: {},
                                typing: {},
                                me: @js((auth()->user()?->id ?? auth('customer')->id()) . ':' . (auth()->check() ? 'User' : 'Customer')),
                                join(){
                                    if (!window.Echo || !this.convId) return;
                                    // Join presence channel
                                    this.presence = window.Echo.join('presence-conversations.'+this.convId)
                                        .here(users => { this.members = Object.fromEntries(users.map(u=>[u.id+':'+u.type,u])); this.sync(); })
                                        .joining(user => { this.members[user.id+':'+user.type]=user; this.sync(); })
                                        .leaving(user => { delete this.members[user.id+':'+user.type]; this.sync(); })
                                        .listenForWhisper('typing', (e)=>{ this.typing[e.key]=Date.now(); this.sync(); })
                                },
                                sync(){
                                    // Purge stale typing (>4s)
                                    const now = Date.now();
                                    for (const k in this.typing) { if (now - this.typing[k] > 4000) delete this.typing[k]; }
                                    const online = Object.values(this.members);
                                    const typing = Object.keys(this.typing);
                                    $wire.dispatch('presence-update', { online, typing });
                                }
                         }" x-init="join()" x-effect="convId=@js($activeConversation?->id); join()">
                        <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <div class="text-sm font-semibold truncate">
                        @if($activeConversation)
                            @php
                                $names = collect($activeConversation->participants)
                                    ->filter(fn($p) => ($p->participant?->id ?? null) !== (auth()->user()->id ?? auth('customer')->id()))
                                    ->map(fn($p) => $p->participant?->name)
                                    ->filter()
                                    ->values();
                                $title = $activeConversation->name ?: $names->implode(', ');
                            @endphp
                            {{ $title }}
                        @else
                            {{ ($this->minimal) ? 'Support' : 'Select a conversation' }}
                        @endif
                    </div>
                    @if($activeConversation)
                        <div class="text-xs text-gray-500 flex items-center gap-2">
                            <span>{{ ucfirst($activeConversation->privacy ?? 'private') }}</span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800">
                                <svg class="size-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                                <span>{{ count($online ?? []) }}</span>
                            </span>
                        </div>
                    @endif
                </div>
                <div class="hidden sm:flex items-center gap-2">
                    <!-- Quick actions (Filament-like) -->
                    @if($activeConversation && $activeConversation->type === 'group')
                        <div class="flex items-center gap-1">
                            <div class="relative" x-data="{ open: false }" @click.away="open=false">
                                <button class="px-2 py-1.5 text-xs rounded-lg bg-primary-600 text-white hover:bg-primary-500" @click="open=!open">Invite</button>
                                <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg p-2">
                                    <div class="text-[11px] text-gray-500 mb-1">Staff</div>
                                    <div class="max-h-40 overflow-auto space-y-1 mb-2">
                                        @foreach($staffOptions as $s)
                                            <button wire:click="inviteParticipant('App\\Models\\User', {{ $s->id }})" class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800">{{ $s->name }}</button>
                                        @endforeach
                                    </div>
                                    @if($activeConversation->privacy !== 'internal')
                                        <div class="text-[11px] text-gray-500 mb-1">Customers</div>
                                        <div class="max-h-40 overflow-auto space-y-1">
                                            @foreach($customerOptions as $c)
                                                <button wire:click="inviteParticipant('App\\Models\\Customer', {{ $c->id }})" class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800">{{ $c->name }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="relative" x-data="{ open: false }" @click.away="open=false">
                                <button class="px-2 py-1.5 text-xs rounded-lg border dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800" @click="open=!open">Privacy</button>
                                <div x-show="open" x-transition class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg p-1">
                                    <button class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-sm" wire:click="updateConversationPrivacy('private')">Private</button>
                                    <button class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-sm" wire:click="updateConversationPrivacy('public')">Public</button>
                                    <button class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-sm" wire:click="updateConversationPrivacy('internal')">Internal</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(auth()->check() && ((method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin')) || (property_exists(auth()->user(), 'role') && auth()->user()->role === 'admin')) && $activeConversation)
                        <div class="relative" x-data="{ open: false }" @click.away="open=false">
                            <button class="px-2 py-1.5 text-xs rounded-lg border dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800" @click="open=!open">Admin</button>
                            <div x-show="open" x-transition class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg p-1">
                                <button class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-sm" wire:click="archiveConversation">Archive</button>
                                <button class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-sm text-red-600" wire:click="deleteConversation">Delete</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    <!-- Messages -->
              <div class="flex-1 min-h-0 overflow-auto px-3 md:px-5 py-3"
                                 x-data="{
                           observer:null,
                           savedHeight:0,
                           previousScroll:0,
                  scroll(){ $nextTick(()=>{ const el = $el; el.scrollTop = el.scrollHeight; }); },
                                     init(){
                                         const convId = @js($activeConversation?->id);
                                         if (window.Echo && convId) {
                                             const ch = window.Echo.private('conversations.'+convId);
                                             ch.listen('.message.created', () => { $wire.$refresh(); this.scroll(); });
                                             ch.listen('.message.updated', () => { $wire.$refresh(); });
                                             ch.listen('.message.deleted', () => { $wire.$refresh(); });
                                             ch.listen('.reaction.toggled', () => { $wire.$refresh(); });
                                         }
                                         // Infinite scroll: observe first message sentinel
                                         this.$nextTick(()=>{
                                             const sentinel = this.$refs.topSentinel;
                                             if (!sentinel) return;
                                             this.observer = new IntersectionObserver((entries)=>{
                                                 entries.forEach(e=>{
                                                     if (e.isIntersecting) { $wire.loadMoreMessages(); }
                                                 });
                                             }, { root: this.$el, threshold: 0.01 });
                                             this.observer.observe(sentinel);
                                         });
                                     }
                                 }"
                                 x-init="scroll()"
                                     @message-sent.window="scroll()"
                                     @chat-opened.window="scroll()"
                                     @messages-prepended.window="(() => { const el = $el; const oldHeight = el.scrollHeight; requestAnimationFrame(()=>{ const newHeight = el.scrollHeight; el.scrollTop = newHeight - oldHeight; }); })()"
                        >
            @if($activeConversation)
                @if(!$inChatQ && $activeConversation && $activeConversation->messages()->count() > $this->messagesPerPage*$this->messagesPage)
                                                <div x-ref="topSentinel" class="h-2"></div>
                @endif
                @php
                    $currentUserId = auth()->user()?->id;
                    $currentCustomerId = auth('customer')->id();
                    $messages = $this->activeMessages;
                    $prev = null;
                @endphp
                @foreach($messages as $msg)
                    @php
                        $isSelf = ($msg->sender_type === App\Models\User::class && $currentUserId && $currentUserId === $msg->sender_id)
                            || ($msg->sender_type === App\Models\Customer::class && $currentCustomerId && $currentCustomerId === $msg->sender_id);
                        $align = $isSelf ? 'justify-end' : 'justify-start';
                        $sameSender = $prev && $prev->sender_type === $msg->sender_type && $prev->sender_id === $msg->sender_id;
                        $withinFiveMin = $prev && $prev->created_at && $msg->created_at && $prev->created_at->diffInMinutes($msg->created_at) <= 5;
                        $continuation = $sameSender && $withinFiveMin;
                        $bubble = $isSelf
                            ? ($continuation ? 'bg-primary-600 text-white rounded-xl rounded-tr-sm' : 'bg-primary-600 text-white rounded-2xl rounded-tr-sm')
                            : ($continuation ? 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-gray-100 rounded-xl rounded-tl-sm' : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-gray-100 rounded-2xl rounded-tl-sm');
                        $dateChanged = !$prev || $prev->created_at->toDateString() !== optional($msg->created_at)->toDateString();
                        $showName = !$isSelf && !$continuation;
                        $avatarInitial = strtoupper(mb_substr($msg->sender?->name ?? 'U', 0, 1));
                    @endphp

                    @if($dateChanged)
                        <div class="my-3 flex items-center gap-3">
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></div>
                            <div class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                {{ optional($msg->created_at)->isToday() ? 'Today' : (optional($msg->created_at)->isYesterday() ? 'Yesterday' : optional($msg->created_at)->toFormattedDateString()) }}
                            </div>
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></div>
                        </div>
                    @endif

                    <div class="mb-1.5 flex {{ $align }}">
                        <div class="max-w-[80%] flex gap-2 {{ $isSelf ? 'flex-row-reverse' : '' }}">
                            @if(!$isSelf)
                                <div class="shrink-0 mt-5 {{ $continuation ? 'opacity-0' : '' }}">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 text-white flex items-center justify-center text-xs font-semibold ring-2 ring-white/70 dark:ring-gray-900">{{ $avatarInitial }}</div>
                                </div>
                            @endif
                            <div class="min-w-0">
                                @if($showName)
                                    <div class="text-[11px] text-gray-500 mb-0.5">{{ $msg->sender?->name ?? 'Unknown' }}</div>
                                @endif
                                <div class="px-3 py-2 {{ $bubble }} shadow-sm">
                                    @if(!$msg->is_deleted)
                                        <div class="whitespace-pre-wrap text-[13px] leading-snug">{{ $msg->body }}</div>
                                    @else
                                        <div class="text-xs italic opacity-70">[deleted]</div>
                                    @endif
                                    @if($msg->attachments->count())
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            @foreach($msg->attachments as $att)
                                                @php $isImage = is_string($att->mime_type) && str_starts_with($att->mime_type, 'image/'); @endphp
                                                @if($isImage)
                                                    <a href="{{ Storage::disk($att->disk)->url($att->path) }}" target="_blank" class="block">
                                                        <img src="{{ Storage::disk($att->disk)->url($att->path) }}" alt="attachment" class="max-h-40 rounded-lg border border-gray-200 dark:border-gray-800 object-cover" />
                                                    </a>
                                                @else
                                                    <a href="{{ Storage::disk($att->disk)->url($att->path) }}" target="_blank" class="block text-xs underline underline-offset-2 {{ $isSelf ? 'text-white/90' : 'text-primary-600' }} truncate">{{ $att->filename }}</a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-0.5 flex items-center gap-2 text-[11px] text-gray-500 {{ $isSelf ? 'justify-end' : '' }}">
                                    <span>{{ $msg->created_at?->format('H:i') }}</span>
                                    @if($msg->is_edited)
                                        <span>edited</span>
                                    @endif
                                    @php $groups = $msg->reactions->groupBy('emoji')->map->count(); @endphp
                                    @if($groups->count())
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-white/70 dark:bg-gray-800/70 border border-gray-200 dark:border-gray-700">
                                            @foreach($groups as $emoji => $count)
                                                <button wire:click="toggleReaction({{ $msg->id }}, '{{ $emoji }}')" class="text-[11px]">{{ $emoji }} {{ $count }}</button>
                                            @endforeach
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-0.5 hidden sm:flex gap-2 {{ $isSelf ? 'justify-end' : '' }}">
                                    <button wire:click="toggleReaction({{ $msg->id }}, '👍')" class="text-[11px] text-gray-500 hover:text-gray-700">👍</button>
                                    <button wire:click="toggleReaction({{ $msg->id }}, '❤️')" class="text-[11px] text-gray-500 hover:text-gray-700">❤️</button>
                                    @if($isSelf && !$msg->is_deleted)
                                        <button x-data @click="(async()=>{ const nv = prompt('Edit message', @js($msg->body)); if(nv !== null){ $wire.editMessage({{ $msg->id }}, nv); } })()" class="text-[11px] text-gray-500 hover:text-gray-700">Edit</button>
                                    @endif
                                    @if(auth()->check() && ((method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin')) || (property_exists(auth()->user(), 'role') && auth()->user()->role === 'admin')))
                                        <button wire:click="deleteMessage({{ $msg->id }})" class="text-[11px] text-red-600 hover:text-red-700">Delete</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @php $prev = $msg; @endphp
                @endforeach
            @endif
        </div>

        <!-- Composer -->
    <div class="px-3 md:px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            @if($activeConversation || ($this->minimal))
         <div class="flex items-end gap-2" x-data="{
                   showEmoji: false, progress: 0, query: '',
                   emojis: ['😀','😁','😂','🤣','😊','😍','😘','😎','🤔','👍','👎','🙏','🔥','🎉','❤️','💯','😭','😡','🤯','🤗','👏','🙌','✨','🍀','🍻','🎁','📎','🖼️'],
                   get filtered(){ if(!this.query) return this.emojis; return this.emojis.filter(e=>e.normalize('NFKD').toLowerCase().includes(this.query.toLowerCase())); },
                   lastKeyAt: 0,
                   typingWhisper(){
                      const now = Date.now(); if (now - this.lastKeyAt < 800) return; this.lastKeyAt = now;
                      const convId = @js($activeConversation?->id); if (!window.Echo || !convId) return;
                      const key = @js((auth()->user()?->id ?? auth('customer')->id()) . ':' . (auth()->check() ? 'User' : 'Customer'));
                      window.Echo.join('presence-conversations.'+convId).whisper('typing', { key });
                   }
                }" @click.away="showEmoji = false"
                 @chat-opened.window="$nextTick(()=>{ $el.querySelector('textarea')?.focus(); })"
                 x-on:livewire-upload-progress.window="progress = $event.detail.progress"
                 x-on:livewire-upload-finish.window="progress = 0"
                 x-on:livewire-upload-error.window="progress = 0"
             >
                    <!-- Emoji button -->
                    <div class="relative">
                        <button type="button" @click="showEmoji = !showEmoji" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800" title="Emoji">😊</button>
                        <div x-show="showEmoji" x-transition class="absolute bottom-11 left-0 z-10 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg p-2">
                            <input type="text" x-model="query" placeholder="Search emoji" class="w-full mb-2 text-xs rounded-lg px-2 py-1 bg-white/70 dark:bg-gray-900/70 border border-gray-200 dark:border-gray-800 focus:outline-none" />
                            <div class="grid grid-cols-8 gap-1 text-xl max-h-40 overflow-auto pr-1">
                                <template x-for="e in filtered" :key="e"><button type="button" class="hover:scale-110" @click="$wire.set('message', ($wire.message || '') + e); showEmoji=false;" x-text="e"></button></template>
                            </div>
                        </div>
                    </div>

                    <!-- Textarea -->
                    <textarea
                        wire:model.defer="message"
                        rows="1"
                        placeholder="{{ $activeConversation ? 'Type a message' : 'Type a message to start a support chat' }}"
                        class="flex-1 min-h-[44px] max-h-40 rounded-xl px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-300"
                        x-on:keydown.enter.prevent="if(!event.shiftKey){ $wire.sendMessage(); }" x-on:keydown="typingWhisper()"
                    ></textarea>

                    <!-- Attach -->
                    <label class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 cursor-pointer" title="Attach files">
                        <input type="file" wire:model="uploads" class="hidden" multiple />
                        <svg class="size-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.375 12.739l-7.693 7.693a4.125 4.125 0 11-5.836-5.836l9.193-9.193a2.75 2.75 0 113.889 3.889l-8.5 8.5a1.375 1.375 0 11-1.945-1.944l7.43-7.43"/></svg>
                    </label>

                    <!-- Send -->
                    <button wire:click="sendMessage" class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-600 hover:bg-primary-500 text-white shadow-md">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M3.404 2.361a.75.75 0 01.823-.12l16.5 7.5a.75.75 0 010 1.36l-16.5 7.5a.75.75 0 01-1.043-.857l1.57-6.284a.75.75 0 01.353-.48l6.468-3.732a.25.25 0 000-.432L4.607 3.697a.75.75 0 01-.356-.48l-1.57-6.284z"/></svg>
                    </button>
                    
                    <!-- Upload progress & selected files -->
                    <div class="mt-2 space-y-1">
                        <div x-show="progress > 0" class="h-1 w-full bg-gray-100 dark:bg-gray-800 rounded">
                            <div class="h-1 bg-primary-500 rounded" :style="`width: ${progress}%;`"></div>
                        </div>
                        @if(!empty($uploads))
                            <div class="flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300">
                                @foreach($uploads as $u)
                                    <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 truncate max-w-[200px]">{{ $u->getClientOriginalName() }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <!-- Typing indicator with names -->
                <div class="mt-1 text-[11px] text-gray-500 min-h-5">
                    @php
                        $typingKeys = collect($typing ?? []);
                        // typing keys are `${id}:${type}`; presence members kept in JS, but we also have $online array with same structure
                        $onlineMembers = collect($online ?? [])->keyBy(function($m){ return ($m['id'] ?? $m->id) . ':' . ($m['type'] ?? $m->type ?? ''); });
                        $typingNames = $typingKeys->keys()->map(function($k) use ($onlineMembers){ return $onlineMembers[$k]['name'] ?? $onlineMembers[$k]->name ?? null; })->filter()->values();
                    @endphp
                    @if($typingNames->isNotEmpty())
                        <div class="flex items-center gap-1 flex-wrap">
                            @foreach($typingNames as $name)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">{{ $name }}<span class="animate-pulse">…</span></span>
                            @endforeach
                        </div>
                    @endif
                </div>
                {{-- Upload progress & selected files removed (duplicate). Progress markup is already inside the composer x-data scope. --}}
                @error('message') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
            @else
                <div class="text-sm text-gray-500">Start a conversation from the left panel.</div>
            @endif
        </div>
    </section>

    <!-- New Chat Modal -->
    <div x-data="{ open: @entangle('showNewChatModal') }" x-show="open" x-transition class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4">
                <div class="text-lg font-semibold mb-2">New Conversation</div>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500">Group name (optional for direct)</label>
                        <input type="text" wire:model.defer="newChatName" class="w-full rounded-xl px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Group privacy</label>
                        <select wire:model.defer="newChatPrivacy" class="w-full rounded-xl px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                            <option value="private">Private</option>
                            <option value="public">Public</option>
                            <option value="internal">Internal (staff only)</option>
                        </select>
                    </div>
                    <div class="text-xs text-gray-500">Create a direct chat with:</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Staff</div>
                            <div class="max-h-40 overflow-auto space-y-1">
                                @foreach($staffOptions as $s)
                                    <button wire:click="createDirectChat('App\\Models\\User', {{ $s->id }})" class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800">{{ $s->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        @if($customerOptions->count())
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Customer</div>
                                <div class="max-h-40 overflow-auto space-y-1">
                                    @foreach($customerOptions as $c)
                                        <button wire:click="createDirectChat('App\\Models\\Customer', {{ $c->id }})" class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800">{{ $c->name }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <button wire:click="createGroup" class="px-3 py-1.5 rounded-xl bg-primary-600 text-white">Create group</button>
                        <button class="px-3 py-1.5 rounded-xl border" @click="open = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
