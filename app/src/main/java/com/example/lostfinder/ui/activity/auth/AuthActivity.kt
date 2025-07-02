package com.example.lostfinder.ui.activity.auth

import android.content.Intent
import android.os.Bundle
import android.util.Log
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.widget.addTextChangedListener
import com.example.lostfinder.R
import com.example.lostfinder.databinding.ActivityAuthBinding
import com.example.lostfinder.ui.activity.DetailsActivity
import com.example.lostfinder.ui.activity.MainActivity
import com.example.lostfinder.utils.PrefManager
import com.google.firebase.FirebaseException
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.PhoneAuthCredential
import com.google.firebase.auth.PhoneAuthOptions
import com.google.firebase.auth.PhoneAuthProvider
import com.google.firebase.auth.ktx.auth
import com.google.firebase.ktx.Firebase
import java.util.concurrent.TimeUnit

class AuthActivity : AppCompatActivity() {
    private lateinit var binding: ActivityAuthBinding
    val TAG = "AuthActivityTAG"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        binding = ActivityAuthBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(24, systemBars.top, 24, systemBars.bottom)
            insets
        }

        val pref = PrefManager(this)

        if (pref.checkIsLogin("isLogin")) {
            startActivity(Intent(this, DetailsActivity::class.java))
            finish()
        }

        binding.phoneEt.addTextChangedListener {
            val length = it?.length ?: 0

            when {
                length == 10 -> {
                    binding.phoneEtL.background = ContextCompat.getDrawable(this, R.drawable.phone_border_green)
                }
                length in 1..9 -> {
                    binding.phoneEtL.background = ContextCompat.getDrawable(this, R.drawable.phone_border_yellow)
                }
                length > 10 -> {
                    binding.phoneEtL.background = ContextCompat.getDrawable(this, R.drawable.phone_border_red)
                }
                length == 0 -> {
                    binding.phoneEtL.background = ContextCompat.getDrawable(this, R.drawable.phone_border_def)
                }
                else -> {
                    binding.phoneEtL.background = ContextCompat.getDrawable(this, R.drawable.phone_border_yellow)
                }
            }
        }


        binding.sendBtn.setOnClickListener {
            val phoneNo = binding.phoneEt.text.toString().trim()
            val ccp = binding.countryCodePicker
            val cc = ccp.selectedCountryCode

            if (phoneNo.isEmpty()) {
                binding.phoneEt.error = "Please enter phone number!"
                return@setOnClickListener
            }

            val countryCodeWithNumber = "+$cc$phoneNo"

            startActivity(Intent(this, OtpActivity::class.java)
                .putExtra("phoneNo", countryCodeWithNumber))
        }

    }
}