<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - TARA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/png" href="assets/icon.png"></head>
<body style="background-color: var(--navy);">

    <section class="auth-section">
        <div class="auth-overlay"></div>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="assets/tara-logo.png" alt="TARA Logo" class="auth-logo">
                    <p>Create a new TARA account</p>
                </div>
                
                <div class="auth-body">
                    <form action="php/auth_register.php" method="POST" enctype="multipart/form-data" id="registrationForm">
                        
                        <div id="registerAlert" class="alert"></div>

                        <h3 class="section-title">Personal Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="first_name" class="form-control" required placeholder="First">
                            </div>
                            <div class="form-group">
                                <label for="middleName">Middle Name</label>
                                <input type="text" id="middleName" name="middle_name" class="form-control" placeholder="Middle (Optional)">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="last_name" class="form-control" required placeholder="Last">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" class="form-control" required min="1">
                            </div>
                            <div class="form-group">
                                <label for="birthdate">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Create a strong password">
                        </div>

                        <h3 class="section-title">User Classification</h3>
                        <p class="section-desc">Please check all that apply:</p>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="classification[]" value="Driver" class="class-checkbox"> Driver</label>
                            <label class="checkbox-label"><input type="checkbox" name="classification[]" value="Regular Commuter" class="class-checkbox"> Regular Commuter</label>
                            <label class="checkbox-label"><input type="checkbox" name="classification[]" value="Student" class="class-checkbox"> Student</label>
                            <label class="checkbox-label"><input type="checkbox" name="classification[]" value="PWD" class="class-checkbox"> PWD (Person with Disability)</label>
                            <label class="checkbox-label"><input type="checkbox" name="classification[]" value="Senior Citizen" class="class-checkbox"> Senior Citizen</label>
                        </div>

                        <div id="driverSection" style="display: none;">
                            <h3 class="section-title">Additional Information for Drivers</h3>
                            <div class="form-group">
                                <label for="todaName">TODA Name</label>
                                <input type="text" id="todaName" name="toda_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="homeAddress">Home Address</label>
                                <input type="text" id="homeAddress" name="home_address" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="memberNumber">Member Number (e.g., #14)</label>
                                <input type="text" id="memberNumber" name="member_number" class="form-control">
                            </div>
                        </div>

                        <div id="identitySection" style="display: none;">
                            <h3 class="section-title">Identity Verification</h3>
                            <p class="section-desc">Please upload the required documents based on your selected type.</p>
                            <p class="section-desc" style="font-size: 0.8rem; font-style: italic;">Note: If you selected multiple categories, please upload all corresponding IDs.</p>
                            
                            <div id="uploadFieldsContainer">
                                <!-- Dynamically populated by JS -->
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary auth-btn" style="margin-top: 20px;">Create Account</button>
                        
                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                            <p style="margin-top: 10px;"><a href="index.php">Back to Home</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkboxes = document.querySelectorAll('.class-checkbox');
            const driverSection = document.getElementById('driverSection');
            const identitySection = document.getElementById('identitySection');
            const uploadFieldsContainer = document.getElementById('uploadFieldsContainer');

            const driverInputs = driverSection.querySelectorAll('input');

            // Define required uploads per classification
            const uploadRequirements = {
                'Driver': [
                    { id: 'valid_id_driver', label: 'Any valid ID (e.g., National ID, PhilHealth, etc.)' },
                    { id: 'mtop_driver', label: 'MTOP (Motorized Tricycle Operator\'s Permit) — Required' },
                    { id: 'license_driver', label: 'Driver\'s License — Required' }
                ],
                'Student': [
                    { id: 'valid_id_student', label: 'Any valid ID' },
                    { id: 'student_id', label: 'Student ID — Required' }
                ],
                'PWD': [
                    { id: 'valid_id_pwd', label: 'Any valid ID' },
                    { id: 'pwd_id', label: 'PWD ID — Required' }
                ],
                'Senior Citizen': [
                    { id: 'valid_id_senior', label: 'Any valid ID' },
                    { id: 'senior_id', label: 'Senior Citizen ID — Required' }
                ],
                'Regular Commuter': [
                    { id: 'valid_id_commuter', label: 'Any valid ID (National ID, PhilHealth, etc.)' }
                ]
            };

            function updateForm() {
                let isDriver = false;
                let selectedClasses = [];

                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        selectedClasses.push(cb.value);
                        if (cb.value === 'Driver') isDriver = true;
                    }
                });

                // Toggle Driver Section
                if (isDriver) {
                    driverSection.style.display = 'block';
                    driverInputs.forEach(input => input.setAttribute('required', 'required'));
                } else {
                    driverSection.style.display = 'none';
                    driverInputs.forEach(input => input.removeAttribute('required'));
                }

                // Toggle Identity Section
                if (selectedClasses.length > 0) {
                    identitySection.style.display = 'block';
                    generateUploadFields(selectedClasses);
                } else {
                    identitySection.style.display = 'none';
                    uploadFieldsContainer.innerHTML = '';
                }
            }

            function generateUploadFields(selectedClasses) {
                uploadFieldsContainer.innerHTML = ''; // Clear current
                
                selectedClasses.forEach(cls => {
                    const reqs = uploadRequirements[cls];
                    if (reqs) {
                        const groupTitle = document.createElement('h4');
                        groupTitle.textContent = `For ${cls}s:`;
                        groupTitle.className = 'upload-group-title';
                        uploadFieldsContainer.appendChild(groupTitle);

                        reqs.forEach(req => {
                            const fg = document.createElement('div');
                            fg.className = 'form-group file-group';
                            
                            const label = document.createElement('label');
                            label.setAttribute('for', req.id);
                            label.textContent = req.label;
                            
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.id = req.id;
                            input.name = req.id;
                            input.className = 'form-control file-input';
                            input.accept = 'image/*,.pdf';
                            input.required = true;

                            fg.appendChild(label);
                            fg.appendChild(input);
                            uploadFieldsContainer.appendChild(fg);
                        });
                    }
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateForm);
            });
        });
    </script>
</body>
</html>
