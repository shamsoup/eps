<?php
// Database connection settings
$host = 'localhost';
$db = 'form_data';
$user = 'root';
$pass = '';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $item = $_POST['item'];
        $points = $_POST['points'];
    $employee_id = $_POST['employee_id'];
    $employee_name = $_POST['employee_name'];
    $time_spent = $_POST['time_spent'];

    // Handle file upload
    if (isset($_FILES['image'])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $message = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["image"]["size"] > 500000) {
            $message = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            // Do not upload file
            $message .= " Sorry, your file was not uploaded.";
        } else {
            // If everything is ok, try to upload file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Insert into database
                $sql = "INSERT INTO submissions (category, item, points, employee_id, time_spent, image) VALUES ('$category', '$item', '$points', '$employee_id' , '$time_spent', '$target_file')";

                if ($conn->query($sql) === TRUE) {
                    $message = "New record created successfully";
                } else {
                    $message = "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                $message .= " Sorry, there was an error uploading your file.";
            }
        }
    }
}
// Function to get the current week's points
function getWeeklyPoints($conn) {
    $sql = "SELECT employee_id, SUM(points) AS weekly_points 
            FROM submissions 
            WHERE WEEK(datetime, 1) = WEEK(CURDATE(), 1) 
            GROUP BY employee_id";
    
    $result = $conn->query($sql);
    $weekly_points = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $weekly_points[$row["employee_id"]] = $row["weekly_points"];
        }
    }
    return $weekly_points;
}

// Function to get total points earned by each employee
function getTotalPoints($conn) {
    $sql = "SELECT employee_id, SUM(points) AS total_points 
            FROM submissions 
            GROUP BY employee_id";

    $result = $conn->query($sql);
    $total_points = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $total_points[$row["employee_id"]] = $row["total_points"];
        }
    }
    return $total_points;
}

// Function to calculate monthly hours available (assuming 6 working days per week and 8 hours per day)
function getMonthlyHours() {
    $work_days_per_week = 6;
    $hours_per_day = 8;
    $weeks_in_month = 4; // Approximate

    return $work_days_per_week * $hours_per_day * $weeks_in_month;
}

// Get weekly points and total points
$weekly_points = getWeeklyPoints($conn);
$total_points = getTotalPoints($conn);

// Total work hours available in a month
$monthly_hours = getMonthlyHours();

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Form</title>
    <style>
        /* Modern CSS Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            padding: 20px;
        }

        header {
            background-color: #000000;
            padding: 20px 0;
            text-align: center;
            color: #fff;
        }

        header h1 {
            margin: 0;
            font-size: 2.5rem;
        }

        nav ul {
            list-style: none;
            padding: 0;
        }

        nav ul li {
            display: inline;
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        nav ul li a:hover {
            text-decoration: underline;
        }

        main {
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        h1 {
            margin-bottom: 20px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        select, input[type="datetime-local"], input[type="file"], input[type="submit"] {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            margin-bottom: 20px;
            color: #4CAF50;
            font-weight: bold;
        }

        #pointsDisplay {
            font-weight: bold;
            margin-bottom: 20px;
        }

        footer {
            text-align: center;
            padding: 20px 0;
            background-color: #000110;
            color: #fff;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
    </style>
    <script>
        const points = {
            engineering: {
                "Initial Site visit": 5,
                "Follow-up site Visit": 2,
                "Documentation & Reporting": 2,
                "Client Dealing": 2,
                "Timing (speed, on time)": 2,
                "Cost control": 5,
                "Contractor Dealing": 2,
                "Quality Inspection": 2,
                "Proper engineering drawings": 2,
                "Handover": 15,
                "Floor plan": 5,
                "3D": 5,
                "Master plan": 7,
                "Client presentation": 5,
                "Estimate /BOQ": 5,
                "Minor complaints": -2,
                "Major issues": -10,
                "Client negative calls": -5,
                "Rework": -20,
                "Late arrival (hourly)": -2,
                "Missing milestone": -10,
                "Attendance": 2,
                "Late coming after 30 minutes": -2
            },
            finance: {
                "Budget Planning": 5,
                "Expense Tracking": 3,
                "Profit & Loss Analysis": 4,
                "Client Consultation": 2,
                "Investment Advice": 6,
                "Tax Preparation": 5,
                "Financial Reporting": 4,
                "Cash Flow Management": 5,
                "Audit Preparation": 3,
                "Risk Assessment": 4,
                "Budget Review": 2,
                "Portfolio Management": 6,
                "Debt Management": 3,
                "Market Analysis": 4,
                "Client Education": 2,
                "Cost Analysis": 3,
                "Compliance Checks": 2,
                "Financial Strategy": 5,
                "Revenue Generation": 6,
                "Investment Portfolio Review": 5
            },
            marketing: {
                "Market Research": 4,
                "Campaign Planning": 5,
                "Brand Development": 6,
                "Content Creation": 3,
                "Social Media Management": 5,
                "SEO Optimization": 4,
                "Email Marketing": 4,
                "Event Planning": 5,
                "Analytics Reporting": 3,
                "Client Outreach": 2,
                "Ad Development": 6,
                "Website Management": 5,
                "Public Relations": 4,
                "Sales Funnel Optimization": 5,
                "Customer Retention Strategies": 4,
                "Marketing Strategy Development": 5,
                "Networking": 3,
                "Collaboration with Sales": 4,
                "Influencer Marketing": 5,
                "Product Launch": 6
            },
            architecture: {
                "Site Analysis": 5,
                "Concept Design": 6,
                "Schematic Design": 4,
                "Design Development": 5,
                "Construction Documentation": 4,
                "Project Management": 6,
                "Client Consultation": 2,
                "Building Codes Compliance": 5,
                "3D Modeling": 5,
                "Rendering": 4,
                "Budgeting": 3,
                "Material Selection": 4,
                "Sustainability Consulting": 5,
                "Presentation Preparation": 3,
                "Site Visits": 2,
                "Construction Administration": 4,
                "Client Communication": 3,
                "Contract Negotiation": 4,
                "Permit Acquisition": 5,
                "Quality Assurance": 5
            }
        };

        function updateSecondDropdown() {
            const categoryDropdown = document.getElementById("category");
            const itemDropdown = document.getElementById("item");
            const pointsDisplay = document.getElementById("pointsDisplay");
            const selectedCategory = categoryDropdown.value;

            // Clear previous options and points
            itemDropdown.innerHTML = "";
            pointsDisplay.textContent = "";

            // Populate the second dropdown based on the selected category
            const items = Object.keys(points[selectedCategory] || {});
            items.forEach(item => {
                const option = document.createElement("option");
                option.value = item;
                option.textContent = item;
                itemDropdown.appendChild(option);
            });
        }

        function updatePoints() {
            const categoryDropdown = document.getElementById("category");
            const itemDropdown = document.getElementById("item");
            const pointsDisplay = document.getElementById("pointsDisplay");

            const selectedCategory = categoryDropdown.value;
            const selectedItem = itemDropdown.value;

            if (selectedCategory && selectedItem) {
                const pointValue = points[selectedCategory][selectedItem];
                pointsDisplay.textContent = `Points: ${pointValue}`;
                document.getElementById("points").value = pointValue; // Store points in hidden input
            } else {
                pointsDisplay.textContent = "";
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Arclif Constructions LLP</h1>
        <nav>
            <ul>
                <li><a href="submit.php">Submit Data</a></li>
                <li><a href="display.php">View Submissions</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="container">
            <h1>Employee Performance Management System</h1>
        </div>
        <div class="container">
                       <?php if (!empty($message)): ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <label for="category">Select Category:</label>
                <select id="category" name="category" onchange="updateSecondDropdown();">
                    <option value="">Select a category</option>
                    <option value="engineering">Engineering</option>
                    <option value="finance">Finance</option>
                    <option value="marketing">Marketing</option>
                    <option value="architecture">Architecture</option>
                </select>

                <label for="item">Select Item:</label>
                <select id="item" name="item" onchange="updatePoints();">
                    <option value="">Select an item</option>
                </select>

                
                <label for="employee_id">Employee ID:</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">Select Employee ID</option>
                    <option value="EM1001">EM1001</option>
                    <option value="EM1002">EM1002</option>
                    <option value="EM1003">EM1003</option>
                    <option value="EM1004">EM1004</option>
                    <option value="EM1005">EM1005</option>
                    <option value="EM1006">EM1006</option>
                    <option value="EM1007">EM1007</option>
                    <option value="EM1008">EM1008</option>
                    <option value="EM1009">EM1009</option>
                    <option value="EM1010">EM1010</option>
                </select>
                <label for="employee_name">Employee Name:</label>
                <select id="employee_name" name="employee_name" required>
                    <option value="">Select Employee Name</option>
                    <option value="John Doe">John Doe</option>
                    <option value="Jane Smith">Jane Smith</option>
                    <option value="Michael Brown">Michael Brown</option>
                       
                </select>
                <label for="time_spent">Time Spent (Hours):</label>
                <select id="time_spent" name="time_spent" required>
                    <option value="">Select time spent</option>
                    <option value="1">1 Hour</option>
                    <option value="2">2 Hours</option>
                    <option value="3">3 Hours</option>
                    <option value="4">4 Hours</option>
                </select>

                <label for="image">Upload Image</label>
                <input type="file" id="image" name="image" accept="image/*">

                <div id="pointsDisplay"></div>
                <input type="hidden" id="points" name="points" value="">
                <input type="submit" value="Submit">
            </form>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2024 Arclif Technologies pvt ltd ,www.arclif.com,all rights reserved</p>
        </div>
    </footer>
</body>
</html>
