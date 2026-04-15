<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Club Management - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Course Club Management</h4>
                        <a href="/admin/menu" class="btn btn-sm btn-outline-light">Admin Menu</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php 
                                $successMessages = is_array($success) ? $success : [$success];
                                foreach ($successMessages as $message): 
                                ?>
                                    <?php echo htmlspecialchars($message); ?><br>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                $errorMessages = is_array($errors) ? $errors : [$errors];
                                foreach ($errorMessages as $message): 
                                ?>
                                    <?php echo htmlspecialchars($message); ?><br>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div id="headerSection" class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Course Holes</h5>
                            <div id="buttonContainer">
                                <button type="button" id="applyEditsBtn" class="btn btn-success me-2" style="padding: 6px 12px; font-size: 14px; line-height: 1; display: none;">
                                    <i class="fas fa-check"></i> Apply Edits
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($clubNames)): ?>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Select Club *</label>
                                    <select class="form-select" id="clubFilter" required>
                                        <option value="">-- Select Club --</option>
                                        <?php foreach ($clubNames as $clubName): ?>
                                            <option value="<?php echo htmlspecialchars($clubName); ?>" <?php echo ($selectedClub === $clubName) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($clubName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Select Gender *</label>
                                    <select class="form-select" id="genderFilter" required>
                                        <option value="">-- Select Gender --</option>
                                        <option value="M" <?php echo ($selectedGender === 'M') ? 'selected' : ''; ?>>Male</option>
                                        <option value="F" <?php echo ($selectedGender === 'F') ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="filterMessage" class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i> Please select both Club and Gender to view course holes.
                        </div>

                        <div id="courseContent" style="display: none;">
                            <div id="selectionHeading" class="alert alert-success text-center" style="display: none;">
                                <h5 class="mb-0">
                                    <span id="selectedClubDisplay"></span> - 
                                    <span id="selectedGenderDisplay"></span> Course
                                </h5>
                            </div>
                            
                            <?php if (!empty($courseClubs)): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <!-- Male Holes Section -->
                                        <?php
                                        $maleHoles = array_filter($courseClubs, function($club) { return $club->getGender() === 'M'; });
                                        usort($maleHoles, function($a, $b) { return $a->getNumberHole() - $b->getNumberHole(); });
                                        
                                        if (!empty($maleHoles)) {
                                            $frontNine = array_filter($maleHoles, function($hole) { return $hole->getNumberHole() <= 9; });
                                            $backNine = array_filter($maleHoles, function($hole) { return $hole->getNumberHole() >= 10; });
                                            
                                            if (!empty($frontNine) || !empty($backNine)) {
                                                echo "<div class='mb-4'>";
                                                
                                                echo "<div class='row'>";
                                                
                                                // Front Nine Column
                                                if (!empty($frontNine)) {
                                                    echo "<div class='col-md-12 col-lg-6 mb-3'>";
                                                    echo "<h6 class='text-primary mb-3'>Front Nine (Holes 1-9)</h6>";
                                                    echo "<div class='table-responsive'>";
                                                    echo "<table class='table table-striped table-hover'>";
                                                    echo "<thead class='table-dark'><tr><th>Hole #</th><th>Hole Name</th><th>Par</th><th>Stroke</th><th class='text-center'>Actions</th></tr></thead>";
                                                    echo "<tbody>";
                                                    foreach ($frontNine as $hole) {
                                                        echo "<tr data-club='" . htmlspecialchars($hole->getNameClub()) . "' data-gender='" . $hole->getGender() . "' data-id='" . $hole->getRowId() . "'>";
                                                        echo "<td><span class='badge bg-primary'>" . $hole->getNumberHole() . "</span></td>";
                                                        echo "<td><input type='text' class='form-control form-control-sm' data-field='name_hole' value='" . htmlspecialchars($hole->getNameHole()) . "' oninput='trackEdit(" . $hole->getRowId() . ", \"name_hole\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='par' value='" . $hole->getPar() . "' min='3' max='5' oninput='trackEdit(" . $hole->getRowId() . ", \"par\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='stroke' value='" . $hole->getStroke() . "' min='1' max='18' oninput='trackEdit(" . $hole->getRowId() . ", \"stroke\", this.value)'></td>";
                                                        echo "<td class='text-center'><a href='/course-club/{$hole->getRowId()}/edit' class='btn btn-outline-primary btn-sm' title='Edit' style='padding: 6px 12px; font-size: 14px; line-height: 1;'>Edit</a></td>";
                                                        echo "</tr>";
                                                    }
                                                    echo "</tbody></table></div></div>";
                                                }
                                                
                                                // Back Nine Column
                                                if (!empty($backNine)) {
                                                    echo "<div class='col-md-12 col-lg-6 mb-3'>";
                                                    echo "<h6 class='text-success mb-3'>Back Nine (Holes 10-18)</h6>";
                                                    echo "<div class='table-responsive'>";
                                                    echo "<table class='table table-striped table-hover'>";
                                                    echo "<thead class='table-dark'><tr><th>Hole #</th><th>Hole Name</th><th>Par</th><th>Stroke</th><th class='text-center'>Actions</th></tr></thead>";
                                                    echo "<tbody>";
                                                    foreach ($backNine as $hole) {
                                                        echo "<tr data-club='" . htmlspecialchars($hole->getNameClub()) . "' data-gender='" . $hole->getGender() . "' data-id='" . $hole->getRowId() . "'>";
                                                        echo "<td><span class='badge bg-primary'>" . $hole->getNumberHole() . "</span></td>";
                                                        echo "<td><input type='text' class='form-control form-control-sm' data-field='name_hole' value='" . htmlspecialchars($hole->getNameHole()) . "' oninput='trackEdit(" . $hole->getRowId() . ", \"name_hole\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='par' value='" . $hole->getPar() . "' min='3' max='5' oninput='trackEdit(" . $hole->getRowId() . ", \"par\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='stroke' value='" . $hole->getStroke() . "' min='1' max='18' oninput='trackEdit(" . $hole->getRowId() . ", \"stroke\", this.value)'></td>";
                                                        echo "<td class='text-center'><a href='/course-club/{$hole->getRowId()}/edit' class='btn btn-outline-primary btn-sm' title='Edit' style='padding: 6px 12px; font-size: 14px; line-height: 1;'>Edit</a></td>";
                                                        echo "</tr>";
                                                    }
                                                    echo "</tbody></table></div></div>";
                                                }
                                                
                                                echo "</div>"; // Close row
                                                echo "</div>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <!-- Female Holes Section -->
                                        <?php
                                        $femaleHoles = array_filter($courseClubs, function($club) { return $club->getGender() === 'F'; });
                                        usort($femaleHoles, function($a, $b) { return $a->getNumberHole() - $b->getNumberHole(); });
                                        
                                        if (!empty($femaleHoles)) {
                                            $frontNine = array_filter($femaleHoles, function($hole) { return $hole->getNumberHole() <= 9; });
                                            $backNine = array_filter($femaleHoles, function($hole) { return $hole->getNumberHole() >= 10; });
                                            
                                            if (!empty($frontNine) || !empty($backNine)) {
                                                echo "<div class='mb-4'>";
                                                
                                                echo "<div class='row'>";
                                                
                                                // Front Nine Column
                                                if (!empty($frontNine)) {
                                                    echo "<div class='col-md-12 col-lg-6 mb-3'>";
                                                    echo "<h6 class='text-primary mb-3'>Front Nine (Holes 1-9)</h6>";
                                                    echo "<div class='table-responsive'>";
                                                    echo "<table class='table table-striped table-hover'>";
                                                    echo "<thead class='table-dark'><tr><th>Hole #</th><th>Hole Name</th><th>Par</th><th>Stroke</th><th class='text-center'>Actions</th></tr></thead>";
                                                    echo "<tbody>";
                                                    foreach ($frontNine as $hole) {
                                                        echo "<tr data-club='" . htmlspecialchars($hole->getNameClub()) . "' data-gender='" . $hole->getGender() . "' data-id='" . $hole->getRowId() . "'>";
                                                        echo "<td><span class='badge bg-primary'>" . $hole->getNumberHole() . "</span></td>";
                                                        echo "<td><input type='text' class='form-control form-control-sm' data-field='name_hole' value='" . htmlspecialchars($hole->getNameHole()) . "' oninput='trackEdit(" . $hole->getRowId() . ", \"name_hole\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='par' value='" . $hole->getPar() . "' min='3' max='5' oninput='trackEdit(" . $hole->getRowId() . ", \"par\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='stroke' value='" . $hole->getStroke() . "' min='1' max='18' oninput='trackEdit(" . $hole->getRowId() . ", \"stroke\", this.value)'></td>";
                                                        echo "<td class='text-center'><a href='/course-club/{$hole->getRowId()}/edit' class='btn btn-outline-primary btn-sm' title='Edit' style='padding: 6px 12px; font-size: 14px; line-height: 1;'>Edit</a></td>";
                                                        echo "</tr>";
                                                    }
                                                    echo "</tbody></table></div></div>";
                                                }
                                                
                                                // Back Nine Column
                                                if (!empty($backNine)) {
                                                    echo "<div class='col-md-12 col-lg-6 mb-3'>";
                                                    echo "<h6 class='text-success mb-3'>Back Nine (Holes 10-18)</h6>";
                                                    echo "<div class='table-responsive'>";
                                                    echo "<table class='table table-striped table-hover'>";
                                                    echo "<thead class='table-dark'><tr><th>Hole #</th><th>Hole Name</th><th>Par</th><th>Stroke</th><th class='text-center'>Actions</th></tr></thead>";
                                                    echo "<tbody>";
                                                    foreach ($backNine as $hole) {
                                                        echo "<tr data-club='" . htmlspecialchars($hole->getNameClub()) . "' data-gender='" . $hole->getGender() . "' data-id='" . $hole->getRowId() . "'>";
                                                        echo "<td><span class='badge bg-primary'>" . $hole->getNumberHole() . "</span></td>";
                                                        echo "<td><input type='text' class='form-control form-control-sm' data-field='name_hole' value='" . htmlspecialchars($hole->getNameHole()) . "' oninput='trackEdit(" . $hole->getRowId() . ", \"name_hole\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='par' value='" . $hole->getPar() . "' min='3' max='5' oninput='trackEdit(" . $hole->getRowId() . ", \"par\", this.value)'></td>";
                                                        echo "<td><input type='number' class='form-control form-control-sm' data-field='stroke' value='" . $hole->getStroke() . "' min='1' max='18' oninput='trackEdit(" . $hole->getRowId() . ", \"stroke\", this.value)'></td>";
                                                        echo "<td class='text-center'><a href='/course-club/{$hole->getRowId()}/edit' class='btn btn-outline-primary btn-sm' title='Edit' style='padding: 6px 12px; font-size: 14px; line-height: 1;'>Edit</a></td>";
                                                        echo "</tr>";
                                                    }
                                                    echo "</tbody></table></div></div>";
                                                }
                                                
                                                echo "</div>"; // Close row
                                                echo "</div>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-golf-ball fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Course Holes Found</h5>
                                    <p class="text-muted">Start by adding your first course hole.</p>
                                    <a href="/course-club/create" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add First Hole
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <script>
        // Dual filter functionality
        function updateFilters() {
            const clubFilter = document.getElementById('clubFilter');
            const genderFilter = document.getElementById('genderFilter');
            const filterMessage = document.getElementById('filterMessage');
            const courseContent = document.getElementById('courseContent');
            const selectionHeading = document.getElementById('selectionHeading');
            const selectedClubDisplay = document.getElementById('selectedClubDisplay');
            const selectedGenderDisplay = document.getElementById('selectedGenderDisplay');
            
            const selectedClub = clubFilter.value;
            const selectedGender = genderFilter.value;
            
            // Show/hide content based on filter selection
            if (selectedClub && selectedGender) {
                filterMessage.style.display = 'none';
                courseContent.style.display = 'block';
                selectionHeading.style.display = 'block';
                
                // Update heading
                selectedClubDisplay.textContent = selectedClub.toUpperCase();
                selectedGenderDisplay.textContent = selectedGender === 'M' ? 'Male' : 'Female';
                
                // Filter rows based on both club and gender
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowClub = row.getAttribute('data-club');
                    const rowGender = row.getAttribute('data-gender');
                    
                    const clubMatch = rowClub === selectedClub;
                    const genderMatch = rowGender === selectedGender;
                    
                    row.style.display = (clubMatch && genderMatch) ? '' : 'none';
                });
                
                // Show/hide sections based on visible rows
                const sections = document.querySelectorAll('#courseContent .mb-4');
                sections.forEach(section => {
                    const visibleRows = section.querySelectorAll('tbody tr');
                    const hasVisibleRows = Array.from(visibleRows).some(row => row.style.display !== 'none');
                    section.style.display = hasVisibleRows ? '' : 'none';
                });
            } else {
                filterMessage.style.display = 'block';
                courseContent.style.display = 'none';
            }
        }
        
        // Back to filters function
        function backToFilters() {
            const clubFilter = document.getElementById('clubFilter');
            const genderFilter = document.getElementById('genderFilter');
            const filterMessage = document.getElementById('filterMessage');
            const courseContent = document.getElementById('courseContent');
            const selectionHeading = document.getElementById('selectionHeading');
            
            // Reset filters
            clubFilter.value = '';
            genderFilter.value = '';
            
            // Show filter message, hide course content
            filterMessage.style.display = 'block';
            courseContent.style.display = 'none';
            selectionHeading.style.display = 'none';
        }
        
        // Function to apply all edits
        function applyEdits() {
            const selectedClub = document.getElementById('clubFilter').value;
            const selectedGender = document.getElementById('genderFilter').value;
            
            if (!selectedClub || !selectedGender) {
                alert('Please select both Club and Gender before applying edits.');
                return;
            }
            
            // Collect ALL visible rows (all 18 holes), including non-edited ones
            const editedData = [];
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const courseId = row.getAttribute('data-id');
                    const nameHoleInput = row.querySelector('input[data-field="name_hole"]');
                    const parInput = row.querySelector('input[data-field="par"]');
                    const strokeInput = row.querySelector('input[data-field="stroke"]');
                    
                    if (courseId && nameHoleInput && parInput && strokeInput) {
                        editedData.push({
                            id: courseId,
                            name_hole: nameHoleInput.value,
                            par: parInput.value,
                            stroke: strokeInput.value
                        });
                    }
                }
            });
            
            if (editedData.length !== 18) {
                alert('Error: Expected 18 holes but found ' + editedData.length + '. Please select a club and gender with all holes defined.');
                return;
            }
            
            // Send batch update request
            fetch('/course-club/batch-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    course_clubs: editedData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear edits storage
                    editedCourseClubs = {};
                    hasEdits = false;
                    
                    // Hide Apply Edits button
                    document.getElementById('applyEditsBtn').style.display = 'none';
                    
                    // Show success message
                    alert(data.message || 'Edits applied successfully');
                    
                    // Reload page to show updates
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + (data.message || 'Failed to apply edits'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error: An error occurred while applying edits. Please check your connection and try again.');
            });
        }
        
        // Store edited course clubs data
        let editedCourseClubs = {};
        
        // Track if any edits have been made
        let hasEdits = false;
        
        // Function to track edits
        function trackEdit(courseId, field, value) {
            if (!editedCourseClubs[courseId]) {
                editedCourseClubs[courseId] = {};
            }
            editedCourseClubs[courseId][field] = value;
            hasEdits = true;
            
            // Show Apply Edits button when edits are made
            const applyBtn = document.getElementById('applyEditsBtn');
            const buttonContainer = document.getElementById('buttonContainer');
            const headerSection = document.getElementById('headerSection');
            
            if (applyBtn) {
                applyBtn.style.display = 'inline-block';
                applyBtn.style.visibility = 'visible';
            }
            if (buttonContainer) {
                buttonContainer.style.display = 'block';
                buttonContainer.style.visibility = 'visible';
            }
            if (headerSection) {
                headerSection.style.display = 'flex';
                headerSection.style.visibility = 'visible';
            }
            
            console.log('Button shown, display:', applyBtn ? applyBtn.style.display : 'button not found');
        }
        
        // Add event listeners
        document.getElementById('clubFilter').addEventListener('change', updateFilters);
        document.getElementById('genderFilter').addEventListener('change', updateFilters);
        document.getElementById('applyEditsBtn').addEventListener('click', applyEdits);
        
        // Initialize filters on page load
        updateFilters();
        
        // Load pending edits from PHP session
        const pendingEditsData = <?php echo json_encode($pendingEdits ?? []); ?>;
        if (Object.keys(pendingEditsData).length > 0) {
            // Load pending edits into editedCourseClubs and highlight cells
            Object.keys(pendingEditsData).forEach(courseId => {
                const edit = pendingEditsData[courseId];
                editedCourseClubs[courseId] = edit;
                
                // Update input values in the table
                const row = document.querySelector(`tr[data-id="${courseId}"]`);
                if (row) {
                    const nameInput = row.querySelector('input[data-field="name_hole"]');
                    const parInput = row.querySelector('input[data-field="par"]');
                    const strokeInput = row.querySelector('input[data-field="stroke"]');
                    
                    if (nameInput) nameInput.value = edit.name_hole;
                    if (parInput) parInput.value = edit.par;
                    if (strokeInput) strokeInput.value = edit.stroke;
                    
                    // Highlight row with pending edits
                    row.style.backgroundColor = '#fff3cd';
                }
            });
            
            // Show Apply Edits button
            const applyBtn = document.getElementById('applyEditsBtn');
            const buttonContainer = document.getElementById('buttonContainer');
            const headerSection = document.getElementById('headerSection');
            
            if (applyBtn) {
                applyBtn.style.display = 'inline-block';
                applyBtn.style.visibility = 'visible';
            }
            if (buttonContainer) {
                buttonContainer.style.display = 'block';
                buttonContainer.style.visibility = 'visible';
            }
            if (headerSection) {
                headerSection.style.display = 'flex';
                headerSection.style.visibility = 'visible';
            }
        }
        
        // Check URL hash for pre-selected filters
        if (window.location.hash) {
            const hashParts = window.location.hash.substring(1).split('-');
            if (hashParts.length === 2) {
                const [club, gender] = hashParts;
                const clubFilter = document.getElementById('clubFilter');
                const genderFilter = document.getElementById('genderFilter');
                
                if (clubFilter && genderFilter) {
                    clubFilter.value = club;
                    genderFilter.value = gender;
                    updateFilters();
                }
            }
        }
        

    </script>
