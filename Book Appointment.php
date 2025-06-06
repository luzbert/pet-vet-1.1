<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'pet_owner') {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_db_username');
define('DB_PASSWORD', 'your_db_password');
define('DB_NAME', 'pet_clinic_db');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_pets = [];
$sql_pets = "SELECT pet_id, name FROM pets WHERE owner_id = ?";
$stmt_pets = $conn->prepare($sql_pets);
$stmt_pets->bind_param("i", $user_id);
$stmt_pets->execute();
$result_pets = $stmt_pets->get_result();
while ($row = $result_pets->fetch_assoc()) {
    $user_pets[] = $row;
}
$stmt_pets->close();

$veterinarians = [];
$sql_vets = "SELECT user_id, first_name, last_name FROM users WHERE role = 'veterinarian'";
$result_vets = $conn->query($sql_vets);
if ($result_vets) {
    while ($row = $result_vets->fetch_assoc()) {
        $veterinarians[] = $row;
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pet_id = $_POST['pet_id'] ?? '';
    $veterinarian_id = $_POST['veterinarian_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = $_POST['reason'] ?? '';

    $full_appointment_datetime = $appointment_date . ' ' . $appointment_time;

    if (empty($pet_id) || empty($veterinarian_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Please fill in all required fields.</div>';
    } else {
        $sql_check_pet = "SELECT COUNT(*) FROM pets WHERE pet_id = ? AND owner_id = ?";
        $stmt_check_pet = $conn->prepare($sql_check_pet);
        $stmt_check_pet->bind_param("ii", $pet_id, $user_id);
        $stmt_check_pet->execute();
        $stmt_check_pet->bind_result($pet_count);
        $stmt_check_pet->fetch();
        $stmt_check_pet->close();

        if ($pet_count == 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Invalid pet selection.</div>';
        } else {
            $sql_insert = "INSERT INTO appointments (pet_id, owner_id, veterinarian_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iisss", $pet_id, $user_id, $veterinarian_id, $full_appointment_datetime, $reason);

            if ($stmt_insert->execute()) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">Appointment requested successfully! It is pending confirmation.</div>';
                $_POST = array();
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Error booking appointment: ' . htmlspecialchars($stmt_insert->error) . '</div>';
            }
            $stmt_insert->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold">🐾 Book Appointment</h1>
            <nav>
                <ul class="flex space-x-6 text-lg">
                    <li><a href="dashboard.php" class="hover:text-blue-200">Home</a></li>
                    <li><a href="my_pets.php" class="hover:text-blue-200">My Pets</a></li>
                    <li><a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-8 p-4 flex-grow">
        <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Schedule a New Appointment</h2>

        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg mx-auto border border-gray-200">
            <?php echo $message; ?>

            <form action="book_appointment.php" method="POST" class="space-y-5">
                <div>
                    <label for="pet_id" class="block text-sm font-medium text-gray-700 mb-1">Select Pet <span class="text-red-500">*</span></label>
                    <select id="pet_id" name="pet_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">-- Choose your pet --</option>
                        <?php foreach ($user_pets as $pet): ?>
                            <option value="<?php echo htmlspecialchars($pet['pet_id']); ?>" <?php echo (($_POST['pet_id'] ?? '') == $pet['pet_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($user_pets)): ?>
                        <p class="mt-2 text-sm text-gray-600">You need to <a href="add_pet.php" class="text-blue-600 hover:underline">add a pet</a> first!</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="veterinarian_id" class="block text-sm font-medium text-gray-700 mb-1">Select Veterinarian <span class="text-red-500">*</span></label>
                    <select id="veterinarian_id" name="veterinarian_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">-- Choose a veterinarian --</option>
                        <?php foreach ($veterinarians as $vet): ?>
                            <option value="<?php echo htmlspecialchars($vet['user_id']); ?>" <?php echo (($_POST['veterinarian_id'] ?? '') == $vet['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="appointment_date" class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" id="appointment_date" name="appointment_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div>
                    <label for="appointment_time" class="block text-sm font-medium text-gray-700 mb-1">Time <span class="text-red-500">*</span></label>
                    <input type="time" id="appointment_time" name="appointment_time" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? '09:00'); ?>">
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit <span class="text-red-500">*</span></label>
                    <textarea id="reason" name="reason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Annual check-up, vaccination, limping, skin issue..." required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Request Appointment
                </button>
            </form>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center text-gray-400 text-sm">
            &copy; <?php echo date('Y'); ?> Pet Clinic. All rights reserved.
        </div>
    </footer>
</body>
</html>
