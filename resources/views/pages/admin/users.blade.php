<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Users')] class extends Component {
    use Toast, WithPagination;

    #[Url]
    public string $role = '';

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $newRole = 'teacher';
    public string $password = '';
    public ?int $classroom_id = null;
    public string $admission_no = '';
    public string $gender = 'F';

    public function updated($property): void
    {
        if (in_array($property, ['role', 'search'])) {
            $this->resetPage();
        }
    }

    public function edit(int $id): void
    {
        $user = User::with('student')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->newRole = $user->role;
        $this->password = '';
        $this->classroom_id = $user->student?->classroom_id;
        $this->admission_no = $user->student?->admission_no ?? '';
        $this->gender = $user->student?->gender ?? 'F';
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'email', 'newRole', 'password', 'classroom_id', 'admission_no', 'gender');
        $this->resetValidation();
    }

    public function save(): void
    {
        $student = User::find($this->editingId)?->student;
        $this->validate([
            'name' => 'required|max:120',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingId)],
            'newRole' => 'required|in:admin,teacher,student',
            'password' => [$this->editingId ? 'nullable' : 'required', 'min:8'],
            'classroom_id' => 'required_if:newRole,student|nullable|exists:classrooms,id',
            'admission_no' => ['required_if:newRole,student', 'nullable', Rule::unique('students')->ignore($student?->id)],
            'gender' => 'required_if:newRole,student|in:M,F',
        ], ['classroom_id.required_if' => 'Choose the learner’s class.', 'admission_no.required_if' => 'Enter the admission number.']);

        DB::transaction(function () {
            $user = User::updateOrCreate(['id' => $this->editingId], array_filter([
                'name' => $this->name, 'email' => $this->email, 'role' => $this->newRole,
                'password' => $this->password ?: null,
            ]));
            if ($this->newRole === 'student') {
                Student::updateOrCreate(['user_id' => $user->id], [
                    'name' => $this->name, 'classroom_id' => $this->classroom_id,
                    'admission_no' => $this->admission_no, 'gender' => $this->gender,
                ]);
            }
        });

        $this->success($this->editingId ? 'User updated.' : 'User created. Share the email and password with them.');
        $this->cancel();
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->error('You cannot delete your own account.');

            return;
        }
        User::findOrFail($id)->delete();
        $this->success('User deleted.');
    }

    public function with(): array
    {
        return [
            'users' => User::with('student.classroom')
                ->when($this->role, fn ($q) => $q->where('role', $this->role))
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->orderByRaw("case role when 'admin' then 0 when 'teacher' then 1 else 2 end")->orderBy('name')
                ->paginate(20),
            'classrooms' => Classroom::orderBy('name')->get(),
        ];
    }
};
?>

<div>
    <x-page-header title="Users" subtitle="Staff and learner accounts. There is no self sign-up, so every account is created here." />

    <div class="grid gap-7 xl:grid-cols-[1fr_24rem] items-start">
        <section>
            <div class="flex flex-wrap gap-3 mb-4">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Name or email" icon="o-magnifying-glass" class="w-64" aria-label="Search users" />
                <select wire:model.live="role" class="select w-36" aria-label="Role">
                    <option value="">All roles</option><option value="admin">Admin</option><option value="teacher">Teacher</option><option value="student">Student</option>
                </select>
            </div>
            <div class="panel overflow-x-auto">
                <table class="table">
                    <thead><tr class="border-b border-rule"><th>Name</th><th class="hidden md:table-cell">Email</th><th>Role</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr wire:key="u{{ $u->id }}" class="border-b border-rule hover:bg-base-200">
                                <td class="font-bold">{{ $u->name }}<span class="block md:hidden text-sm font-normal text-muted">{{ $u->email }}</span></td>
                                <td class="hidden md:table-cell">{{ $u->email }}</td>
                                <td>{{ ucfirst($u->role) }}@if ($u->student) <span class="text-muted">· {{ $u->student->classroom->name }}</span>@endif</td>
                                <td class="text-right whitespace-nowrap">
                                    <button wire:click="edit({{ $u->id }})" class="font-bold text-primary underline cursor-pointer">Edit</button>
                                    @unless ($u->id === auth()->id())
                                        <button wire:click="delete({{ $u->id }})" wire:confirm="Delete the account for {{ $u->name }}? Learner records and scores are kept." class="ml-3 text-error underline cursor-pointer">Delete</button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        </section>

        <form wire:submit="save" class="panel p-5 xl:sticky xl:top-6">
            <h2 class="heading text-lg mb-4">{{ $editingId ? 'Edit user' : 'New user' }}</h2>
            <div class="grid gap-3">
                <x-input label="Full name" wire:model="name" />
                <x-input label="Email" type="email" wire:model="email" />
                <x-select label="Role" wire:model.live="newRole" :options="[['id' => 'teacher', 'name' => 'Teacher'], ['id' => 'admin', 'name' => 'Admin (head teacher)'], ['id' => 'student', 'name' => 'Student']]" />
                <x-password label="{{ $editingId ? 'New password (leave blank to keep)' : 'Password' }}" wire:model="password" right />
                @if ($newRole === 'student')
                    <div class="grid grid-cols-2 gap-3">
                        <x-select label="Class" wire:model="classroom_id" :options="$classrooms" placeholder="Choose" />
                        <x-select label="Gender" wire:model="gender" :options="[['id' => 'F', 'name' => 'Female'], ['id' => 'M', 'name' => 'Male']]" />
                    </div>
                    <x-input label="Admission number" wire:model="admission_no" placeholder="VHS/S1/001" />
                @endif
                <div class="flex gap-2 mt-2">
                    <x-button type="submit" :label="$editingId ? 'Save changes' : 'Create user'" spinner="save" class="btn-primary" />
                    @if ($editingId)<x-button label="Cancel" wire:click="cancel" class="btn-ghost" />@endif
                </div>
            </div>
        </form>
    </div>
</div>
