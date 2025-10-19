@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="profileManagement()">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Profile Management</h1>
        <p class="text-gray-600">Update your personal information and manage account settings</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'personal'"
                    :class="activeTab === 'personal' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-2 px-4 border-b-2 font-medium text-sm">
                    Personal Information
                </button>
                <button @click="activeTab = 'notifications'"
                    :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-2 px-4 border-b-2 font-medium text-sm">
                    Notification Preferences
                </button>
                <button @click="activeTab = 'security'"
                    :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-2 px-4 border-b-2 font-medium text-sm">
                    Security
                </button>
                <button @click="activeTab = 'documents'"
                    :class="activeTab === 'documents' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-2 px-4 border-b-2 font-medium text-sm">
                    Documents
                </button>
            </nav>
        </div>
    </div>

    <!-- Personal Information Tab -->
    <div x-show="activeTab === 'personal'" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Personal Information</h2>

        <form @submit.prevent="updateProfile" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" x-model="profile.first_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" x-model="profile.last_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                    <input type="text" x-model="profile.middle_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                    <input type="text" x-model="profile.suffix" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" x-model="profile.email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" x-model="profile.phone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <input type="text" x-model="profile.address" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Birth Date</label>
                    <input type="date" x-model="profile.birth_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                    <select x-model="profile.civil_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Civil Status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                        <option value="Legally Separated">Legally Separated</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                    <select x-model="profile.gender" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type</label>
                    <input type="text" x-model="profile.blood_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" @click="loadProfile" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Reset
                </button>
                <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                    <span x-show="!saving">Save Changes</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Notification Preferences Tab -->
    <div x-show="activeTab === 'notifications'" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Notification Preferences</h2>

        <form @submit.prevent="updateNotificationPreferences" class="space-y-4">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Email Notifications</h3>
                        <p class="text-sm text-gray-500">Receive notifications via email</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.email_notifications" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">In-App Notifications</h3>
                        <p class="text-sm text-gray-500">Show notifications in the dashboard</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.in_app_notifications" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Leave Status Updates</h3>
                        <p class="text-sm text-gray-500">Get notified when leave status changes</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.leave_status_updates" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Approval Notifications</h3>
                        <p class="text-sm text-gray-500">Notifications when applications are approved</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.approval_notifications" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Deadline Reminders</h3>
                        <p class="text-sm text-gray-500">Reminders for important deadlines</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.deadline_reminders" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Policy Updates</h3>
                        <p class="text-sm text-gray-500">HR policy and procedure updates</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.policy_updates" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                    <span x-show="!saving">Save Preferences</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Security Tab -->
    <div x-show="activeTab === 'security'" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Security Settings</h2>

        <div class="space-y-6">
            <!-- Password Change -->
            <div>
                <h3 class="text-md font-medium text-gray-900 mb-4">Change Password</h3>
                <form @submit.prevent="changePassword" class="space-y-4 max-w-md">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" x-model="password.current_password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" x-model="password.password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" x-model="password.password_confirmation" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <button type="submit" :disabled="changingPassword" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!changingPassword">Change Password</span>
                        <span x-show="changingPassword">Changing...</span>
                    </button>
                </form>
            </div>

            <!-- Account Activity -->
            <div>
                <h3 class="text-md font-medium text-gray-900 mb-4">Account Activity</h3>
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="text-sm text-gray-600">Last login: <span class="font-medium text-gray-900">{{ now()->format('Y-m-d H:i:s') }}</span></p>
                    <p class="text-sm text-gray-600 mt-1">IP Address: <span class="font-medium text-gray-900">127.0.0.1</span></p>
                    <p class="text-sm text-gray-600 mt-1">Browser: <span class="font-medium text-gray-900">Chrome 120.0</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Tab -->
    <div x-show="activeTab === 'documents'" class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Document Management</h2>
            <button @click="showUploadModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Upload Document
            </button>
        </div>

        <!-- Storage Usage -->
        <div class="bg-gray-50 p-4 rounded-md mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-900">Storage Usage</p>
                    <p class="text-xs text-gray-500" x-text="`${Math.round(storageUsed / 1024 / 1024)}MB of ${Math.round(storageLimit / 1024 / 1024)}MB used`"></p>
                </div>
                <div class="w-64 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${(storageUsed / storageLimit) * 100}%`"></div>
                </div>
            </div>
        </div>

        <!-- Documents List -->
        <div class="space-y-4">
            <template x-for="doc in documents" :key="doc.id">
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900" x-text="doc.document_type"></h3>
                            <p class="text-xs text-gray-500 mt-1" x-text="doc.category"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="doc.upload_date"></p>
                            <p x-show="doc.description" class="text-sm text-gray-600 mt-2" x-text="doc.description"></p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="downloadDocument(doc)" class="text-blue-600 hover:text-blue-800 text-sm">Download</button>
                            <button @click="deleteDocument(doc.id)" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center space-x-4">
                        <span class="text-xs text-gray-500" x-text="`${Math.round(doc.file_size / 1024)}KB`"></span>
                        <span class="text-xs" :class="doc.is_verified ? 'text-green-600' : 'text-yellow-600'" x-text="doc.is_verified ? 'Verified' : 'Pending Verification'"></span>
                    </div>
                </div>
            </template>

            <div x-show="documents.length === 0" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-sm">No documents uploaded yet</p>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showUploadModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showUploadModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Upload Document
                            </h3>

                            <form @submit.prevent="uploadDocument" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                                    <input type="text" x-model="newDocument.document_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <select x-model="newDocument.category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Select Category</option>
                                        <option value="Personal">Personal</option>
                                        <option value="Education">Education</option>
                                        <option value="Training">Training</option>
                                        <option value="Medical">Medical</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea x-model="newDocument.description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional description"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">File</label>
                                    <input type="file" @change="handleFileSelect" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <p class="text-xs text-gray-500 mt-1">PDF, DOC, DOCX, JPG, JPEG, PNG files only (Max 10MB)</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="uploadDocument()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Upload
                    </button>
                    <button type="button" @click="showUploadModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function profileManagement() {
    return {
        activeTab: 'personal',
        profile: {},
        preferences: {},
        password: {
            current_password: '',
            password: '',
            password_confirmation: ''
        },
        documents: [],
        newDocument: {
            document_type: '',
            category: '',
            description: '',
            file: null
        },
        storageUsed: 0,
        storageLimit: 50 * 1024 * 1024, // 50MB
        showUploadModal: false,
        saving: false,
        changingPassword: false,

        init() {
            this.loadProfile();
            this.loadDocuments();
        },

        async loadProfile() {
            try {
                const response = await fetch('/employee-portal/profile');
                const data = await response.json();

                this.profile = data.employee;
                this.preferences = data.notification_preferences;
            } catch (error) {
                console.error('Error loading profile:', error);
                this.showNotification('Error loading profile data', 'error');
            }
        },

        async updateProfile() {
            this.saving = true;

            try {
                const response = await fetch('/employee-portal/profile', {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.profile)
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Profile updated successfully', 'success');
                } else {
                    this.showNotification(data.error || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                this.showNotification('An error occurred while updating your profile', 'error');
            } finally {
                this.saving = false;
            }
        },

        async updateNotificationPreferences() {
            this.saving = true;

            try {
                const response = await fetch('/employee-portal/profile/notifications', {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.preferences)
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Notification preferences updated successfully', 'success');
                } else {
                    this.showNotification(data.error || 'Failed to update preferences', 'error');
                }
            } catch (error) {
                console.error('Error updating preferences:', error);
                this.showNotification('An error occurred while updating preferences', 'error');
            } finally {
                this.saving = false;
            }
        },

        async changePassword() {
            if (!this.password.current_password || !this.password.password || !this.password.password_confirmation) {
                this.showNotification('Please fill in all password fields', 'error');
                return;
            }

            if (this.password.password !== this.password.password_confirmation) {
                this.showNotification('New passwords do not match', 'error');
                return;
            }

            this.changingPassword = true;

            try {
                const response = await fetch('/employee-portal/profile/change-password', {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.password)
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Password changed successfully', 'success');
                    this.password = {
                        current_password: '',
                        password: '',
                        password_confirmation: ''
                    };
                } else {
                    this.showNotification(data.error || 'Failed to change password', 'error');
                }
            } catch (error) {
                console.error('Error changing password:', error);
                this.showNotification('An error occurred while changing password', 'error');
            } finally {
                this.changingPassword = false;
            }
        },

        async loadDocuments() {
            try {
                const response = await fetch('/employee-portal/documents');
                const data = await response.json();

                this.documents = data.documents;
                this.storageUsed = data.storage_used;
                this.storageLimit = data.storage_limit;
            } catch (error) {
                console.error('Error loading documents:', error);
            }
        },

        async uploadDocument() {
            if (!this.newDocument.document_type || !this.newDocument.category || !this.newDocument.file) {
                this.showNotification('Please fill in all required fields', 'error');
                return;
            }

            const formData = new FormData();
            Object.keys(this.newDocument).forEach(key => {
                formData.append(key, this.newDocument[key]);
            });

            try {
                const response = await fetch('/employee-portal/documents', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Document uploaded successfully', 'success');
                    this.showUploadModal = false;
                    this.newDocument = {
                        document_type: '',
                        category: '',
                        description: '',
                        file: null
                    };
                    this.loadDocuments();
                } else {
                    this.showNotification(data.error || 'Failed to upload document', 'error');
                }
            } catch (error) {
                console.error('Error uploading document:', error);
                this.showNotification('An error occurred while uploading document', 'error');
            }
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    this.showNotification('File size must be less than 10MB', 'error');
                    return;
                }

                // Validate file type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    this.showNotification('Invalid file type. Only PDF, DOC, DOCX, JPG, JPEG, PNG files are allowed', 'error');
                    return;
                }

                this.newDocument.file = file;
            }
        },

        downloadDocument(doc) {
            window.open(`/employee-portal/documents/${doc.id}/download`, '_blank');
        },

        async deleteDocument(docId) {
            if (!confirm('Are you sure you want to delete this document?')) {
                return;
            }

            try {
                const response = await fetch(`/employee-portal/documents/${docId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    this.showNotification('Document deleted successfully', 'success');
                    this.loadDocuments();
                } else {
                    this.showNotification(data.error || 'Failed to delete document', 'error');
                }
            } catch (error) {
                console.error('Error deleting document:', error);
                this.showNotification('An error occurred while deleting document', 'error');
            }
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }
}
</script>
@endpush
@endsection