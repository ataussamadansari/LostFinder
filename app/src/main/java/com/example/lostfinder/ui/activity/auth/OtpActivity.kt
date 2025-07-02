package com.example.lostfinder.ui.activity.auth

import android.content.Intent
import android.os.Bundle
import android.text.Editable
import android.text.InputFilter
import android.text.TextWatcher
import android.util.Log
import android.view.KeyEvent
import android.view.View
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.lostfinder.databinding.ActivityOtpBinding
import com.example.lostfinder.ui.activity.DetailsActivity
import com.example.lostfinder.ui.activity.MainActivity
import com.example.lostfinder.utils.PrefManager

class OtpActivity : AppCompatActivity() {
    private lateinit var binding: ActivityOtpBinding

    val TAG = "OTPActivityTAG"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        binding = ActivityOtpBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(24, systemBars.top, 24, systemBars.bottom)
            insets
        }

        val phoneNo = intent.getStringExtra("phoneNo")
        Log.d(TAG, "phoneNo: $phoneNo")

        val pref = PrefManager(this)

        setupOtpInputs()

        binding.verifyOtpBtn.setOnClickListener {
            val otp = getEnteredOtp()
            if (phoneNo == "") {
                finish()
                return@setOnClickListener
            }

            if (otp.isEmpty()) {
                binding.otpError.visibility = View.VISIBLE
                binding.otpError.text = "Please enter the OTP"
                return@setOnClickListener
            } else if (otp == "1234") {
                pref.saveIsLogin("isLogin", true, "phoneNo", phoneNo.toString())
                startActivity(Intent(this, DetailsActivity::class.java))
                finish()
            } else {
                binding.otpError.visibility = View.VISIBLE
                binding.otpError.text = "Incorrect OTP. Please try again."
            }
        }

    }

    fun getEnteredOtp(): String {
        return binding.otp1.text.toString() +
                binding.otp2.text.toString() +
                binding.otp3.text.toString() +
                binding.otp4.text.toString()
    }

    private fun setupOtpInputs() {
        val otpInputs = listOf(binding.otp1, binding.otp2, binding.otp3, binding.otp4)

        for (i in otpInputs.indices) {
            otpInputs[i].apply {
                // Only 1 character max
                filters = arrayOf(InputFilter.LengthFilter(1))
                isCursorVisible = true

                // Move to next when 1 char entered
                addTextChangedListener(object : TextWatcher {
                    override fun beforeTextChanged(
                        s: CharSequence?,
                        start: Int,
                        count: Int,
                        after: Int
                    ) {
                    }

                    override fun onTextChanged(
                        s: CharSequence?,
                        start: Int,
                        before: Int,
                        count: Int
                    ) {
                        // If user types, move to next
                        if (s?.length == 1 && i < otpInputs.size - 1) {
                            otpInputs[i + 1].requestFocus()
                            binding.otpError.visibility = View.GONE
                        }
                    }

                    override fun afterTextChanged(s: Editable?) {}
                })

                // Handle backspace even if text is not empty
                setOnKeyListener { v, keyCode, event ->
                    if (event.action == KeyEvent.ACTION_DOWN && keyCode == KeyEvent.KEYCODE_DEL) {
                        if (text.isNullOrEmpty() && i > 0) {
                            otpInputs[i - 1].requestFocus()
                            otpInputs[i - 1].setText("")
                        }
                    }
                    false
                }
            }
        }
    }


}