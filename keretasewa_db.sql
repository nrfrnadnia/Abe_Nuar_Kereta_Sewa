-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2026 at 12:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `keretasewa_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL,
  `adminName` varchar(100) NOT NULL,
  `adminPass` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminID`, `adminName`, `adminPass`) VALUES
(1, 'Afrina', '$2y$10$dGPVyvUqjr1ZChV4n4OmouPQ.mKbNv2DART1ipqUwJ4KMXMthDyiu'),
(2, 'aleya', '$2y$10$yG.Pnt558MmM72/uKGXuW.FXp7DlulCf9LGL/d6f/ABP9mHeFGbSG');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bookID` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `carID` int(11) NOT NULL,
  `pickupDate` date NOT NULL,
  `pickupTime` time NOT NULL,
  `returnDate` date NOT NULL,
  `returnTime` time NOT NULL,
  `pickupLoc` varchar(100) NOT NULL,
  `rentTotal` varchar(20) NOT NULL,
  `address` varchar(500) NOT NULL,
  `bookStatus` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bookID`, `custID`, `carID`, `pickupDate`, `pickupTime`, `returnDate`, `returnTime`, `pickupLoc`, `rentTotal`, `address`, `bookStatus`) VALUES
(1, 1, 5, '2026-07-07', '08:30:00', '2026-07-08', '12:00:00', 'Kolej do', 'RM 10.00', 'Kolej do, uitm machang', 'Deletion Accepted'),
(2, 1, 8, '2026-07-07', '08:30:00', '2026-07-08', '12:00:00', 'Kolej do', 'RM 12.00', 'Kolej do, uitm machang', 'Completed'),
(3, 2, 7, '2026-07-08', '17:09:00', '2026-07-08', '17:11:00', 'Kolej do', 'RM 11.00', 'Kolej do, uitm machang', 'Completed'),
(4, 1, 3, '2026-07-07', '18:04:00', '2026-07-07', '19:04:00', 'Kolej do', 'RM 9.00', 'Kolej do, uitm machang', 'Accepted'),
(5, 2, 7, '2026-07-09', '18:05:00', '2026-07-09', '20:05:00', 'Kolej do', 'RM 11.00', 'Kolej do, uitm machang', 'Rejected'),
(6, 3, 2, '2026-07-07', '18:20:00', '2026-07-08', '20:20:00', 'machang', 'RM 8.00', 'stesen bas machang', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `car`
--

CREATE TABLE `car` (
  `carID` int(11) NOT NULL,
  `carPlate` varchar(20) NOT NULL,
  `carBrand` varchar(100) NOT NULL,
  `carModel` varchar(100) NOT NULL,
  `carPrice` decimal(10,2) NOT NULL,
  `carAvailability` varchar(20) DEFAULT 'Available',
  `carImage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `car`
--

INSERT INTO `car` (`carID`, `carPlate`, `carBrand`, `carModel`, `carPrice`, `carAvailability`, `carImage`) VALUES
(2, 'ENT 300', 'Perodua', 'MYVI 1st 1.3', 8.00, 'Available', 'myvi1st1.3.jpg'),
(3, 'MAT 210', 'Perodua', 'AXIA G 1.0', 9.00, 'Available', 'axiag1.0.jpg'),
(4, 'ICT 300', 'Perodua', 'AXIA X 1.0 2023', 10.00, 'Available', 'axiax1.0.png'),
(5, 'STA 116', 'Perodua', 'BEZZA 1.0 2023', 10.00, 'Available', 'bezza.png'),
(6, 'ITT300', 'Perodua', 'MYVI ICON 1.5', 10.00, 'Available', 'myviicon1.5.jpg'),
(7, 'ITT 270', 'Perodua', 'MYVI GEN 3rd 1.3', 11.00, 'Available', 'myvigen3rd.png'),
(8, 'CSC 264', 'Perodua', 'ALZA 2020 1.5', 12.00, 'Available', 'alza2020.png'),
(9, 'ISP 250', 'Perodua ', 'ALZA 1.5H 2023', 15.00, 'Available', 'alza1.5h.png'),
(10, 'CSC 253', 'Perodua', 'VIVA 1.0', 7.00, 'Available', 'viva1st.png');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `custID` int(11) NOT NULL,
  `custIC` varchar(20) NOT NULL,
  `custName` varchar(100) NOT NULL,
  `custType` varchar(50) NOT NULL,
  `contactno` varchar(20) NOT NULL,
  `custEmail` varchar(100) NOT NULL,
  `custLicense` varchar(255) DEFAULT 'imgweb/default-license.jpg',
  `custStudentCard` varchar(255) DEFAULT 'imgweb/default-studentcard.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`custID`, `custIC`, `custName`, `custType`, `contactno`, `custEmail`, `custLicense`, `custStudentCard`) VALUES
(1, '061115140548', 'afrina dania', 'Public', '01175374180', 'afrina@gmail.com', 'uploads/license_061115140548_1783332275.png', 'uploads/student_061115140548_1783323502.png'),
(2, '061115145544', 'ddania', 'Student', '01175374180', 'dania@gmail.com', 'uploads/license_061115145544_1783332339.png', 'uploads/student_061115145544_1783332339.png'),
(3, '061115145543', 'nizam', 'Public', '01140036538', 'nizam@gmail.com', 'uploads/license_061115145543_1783333261.png', 'imgweb/default-studentcard.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payID` int(11) NOT NULL,
  `bookID` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `carID` int(11) NOT NULL,
  `returnID` int(11) DEFAULT NULL,
  `penID` int(11) DEFAULT NULL,
  `dayTotal` int(11) NOT NULL,
  `timeTotal` int(11) NOT NULL,
  `payTotal` decimal(10,2) NOT NULL,
  `payProof` varchar(255) DEFAULT 'img/default-pay.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payID`, `bookID`, `custID`, `carID`, `returnID`, `penID`, `dayTotal`, `timeTotal`, `payTotal`, `payProof`) VALUES
(1, 3, 2, 7, NULL, NULL, 0, 160, 11.00, 'imgweb/1783330006_Screenshot 2026-03-12 032622.png'),
(2, 2, 1, 8, NULL, NULL, 0, 160, 12.00, 'imgweb/1783330128_Screenshot 2026-03-12 220201.png');

-- --------------------------------------------------------

--
-- Table structure for table `penalty`
--

CREATE TABLE `penalty` (
  `penID` int(11) NOT NULL,
  `bookID` int(11) NOT NULL,
  `penDescrp` text NOT NULL,
  `penTotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_update`
--

CREATE TABLE `return_update` (
  `returnID` int(11) NOT NULL,
  `bookID` int(11) NOT NULL,
  `custID` int(11) NOT NULL,
  `carID` int(11) NOT NULL,
  `carLoc` varchar(500) NOT NULL,
  `returnDate` date NOT NULL,
  `returnTime` time NOT NULL,
  `carCondition` varchar(255) DEFAULT 'img/default-carCondition.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_update`
--

INSERT INTO `return_update` (`returnID`, `bookID`, `custID`, `carID`, `carLoc`, `returnDate`, `returnTime`, `carCondition`) VALUES
(1, 3, 2, 7, 'kolej do', '2026-07-06', '17:25:00', ''),
(2, 2, 1, 8, 'kolej do', '2026-07-06', '17:28:00', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bookID`),
  ADD KEY `custID` (`custID`),
  ADD KEY `carID` (`carID`);

--
-- Indexes for table `car`
--
ALTER TABLE `car`
  ADD PRIMARY KEY (`carID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`custID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payID`),
  ADD KEY `bookID` (`bookID`),
  ADD KEY `returnID` (`returnID`),
  ADD KEY `custID` (`custID`),
  ADD KEY `carID` (`carID`),
  ADD KEY `penID` (`penID`);

--
-- Indexes for table `penalty`
--
ALTER TABLE `penalty`
  ADD PRIMARY KEY (`penID`),
  ADD KEY `bookID` (`bookID`);

--
-- Indexes for table `return_update`
--
ALTER TABLE `return_update`
  ADD PRIMARY KEY (`returnID`),
  ADD KEY `bookID` (`bookID`),
  ADD KEY `custID` (`custID`),
  ADD KEY `carID` (`carID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bookID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `car`
--
ALTER TABLE `car`
  MODIFY `carID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `custID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `penID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_update`
--
ALTER TABLE `return_update`
  MODIFY `returnID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`carID`) REFERENCES `car` (`carID`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`bookID`) REFERENCES `booking` (`bookID`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`returnID`) REFERENCES `return_update` (`returnID`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_4` FOREIGN KEY (`carID`) REFERENCES `car` (`carID`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_5` FOREIGN KEY (`penID`) REFERENCES `penalty` (`penID`) ON DELETE SET NULL;

--
-- Constraints for table `penalty`
--
ALTER TABLE `penalty`
  ADD CONSTRAINT `penalty_ibfk_1` FOREIGN KEY (`bookID`) REFERENCES `booking` (`bookID`) ON DELETE CASCADE;

--
-- Constraints for table `return_update`
--
ALTER TABLE `return_update`
  ADD CONSTRAINT `return_update_ibfk_1` FOREIGN KEY (`bookID`) REFERENCES `booking` (`bookID`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_update_ibfk_2` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_update_ibfk_3` FOREIGN KEY (`carID`) REFERENCES `car` (`carID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
