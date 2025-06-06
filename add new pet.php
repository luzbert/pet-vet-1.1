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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $species = $_POST['species'] ?? '';
    $breed = $_POST['breed'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $microchip_id = $_POST['microchip_id'] ?? null;

    if (empty($name) || empty($species) || empty($breed) || empty($age) || empty($gender)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Please fill in all required fields.</div>';
    } else {
        $sql = "INSERT INTO pets (owner_id, name, species, breed, age, gender, microchip_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $stmt->bind_param("issssis", $user_id, $name, $species, $breed, $age, $gender, $microchip_id);

        if ($stmt->execute()) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">Pet added successfully! <a href="my_pets.php" class="font-semibold underline">View your pets</a></div>';
            $_POST = array();
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Error adding pet: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Pet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold">🐾 Add New Pet</h1>
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
        <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Register a New Pet</h2>

        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg mx-auto border border-gray-200">
            <?php echo $message; ?>

            <form action="add_pet.php" method="POST" class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Pet Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div>
                    <label for="species" class="block text-sm font-medium text-gray-700 mb-1">Species <span class="text-red-500">*</span></label>
                    <select id="species" name="species" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Species</option>
                        <option value="Dog" <?php echo (($_POST['species'] ?? '') == 'Dog') ? 'selected' : ''; ?>>Dog</option>
                        <option value="Cat" <?php echo (($_POST['species'] ?? '') == 'Cat') ? 'selected' : ''; ?>>Cat</option>
                        <option value="Bird" <?php echo (($_POST['species'] ?? '') == 'Bird') ? 'selected' : ''; ?>>Bird</option>
                        <option value="Other" <?php echo (($_POST['species'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="breed" class="block text-sm font-medium text-gray-700 mb-1">Breed <span class="text-red-500">*</span></label>
                    <input type="text" id="breed" name="breed" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required value="<?php echo htmlspecialchars($_POST['breed'] ?? ''); ?>">
                </div>
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age (Years) <span class="text-red-500">*</span></label>
                    <input type="number" id="age" name="age" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                </div>
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                    <select id="gender" name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Unknown" <?php echo (($_POST['gender'] ?? '') == 'Unknown') ? 'selected' : ''; ?>>Unknown</option>
                    </select>
                </div>
                <div>
                    <label for="microchip_id" class="block text-sm font-medium text-gray-700 mb-1">Microchip ID (Optional)</label>
                    <input type="text" id="microchip_id" name="microchip_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($_POST['microchip_id'] ?? ''); ?>">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Add Pet
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
