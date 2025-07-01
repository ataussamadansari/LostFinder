package com.example.lostfinder.model

data class DriverDetails(
    val name: String,
    val email: String?,                       // Optional
    val phoneNumber: String,
    val address: String,
    val licenseNumber: String,               // Driver License (DL)
    val vehicleNumber: String,               // e.g., MH12AB1234
    val vehicleType: String,                 // Car, Bike, Truck
    val vehicleModel: String?,               // e.g., Swift Dzire
    val vehicleColor: String?,               // e.g., White, Red
    val profileImageUrl: String?,            // Driver photo URL
    val rating: Float?,                      // Optional rating
    val experienceYears: Int?,               // Optional experience
    val verified: Boolean = false,           // Is verified by admin?

    // KYC Details
    val kycType: KycType,                    // Enum: AADHAAR, PAN
    val kycNumber: String,                   // Masked or full (depending on use)
    val kycFrontImageUrl: String?,           // Image of Aadhar/PAN front
    val kycBackImageUrl: String?,            // Only if Aadhar has back image
)


