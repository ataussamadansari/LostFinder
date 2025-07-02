package com.example.lostfinder.ui.activity

import android.animation.ArgbEvaluator
import android.animation.ValueAnimator
import android.graphics.drawable.GradientDrawable
import android.os.Bundle
import android.transition.ChangeBounds
import android.transition.TransitionManager
import android.view.LayoutInflater
import android.view.View
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.constraintlayout.widget.ConstraintSet
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.lostfinder.R
import com.example.lostfinder.databinding.ActivityDetailsBinding
import com.example.lostfinder.databinding.SetUserCustomBtnBinding

class DetailsActivity : AppCompatActivity() {
    private lateinit var binding: ActivityDetailsBinding
    private lateinit var setDetailsBinding: SetUserCustomBtnBinding
    private var isCurrentTab: String = "rider"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        binding = ActivityDetailsBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, 0, systemBars.right, systemBars.bottom)
            insets
        }

        val includeBtn = findViewById<View>(R.id.include_btn)

        setDetailsBinding = SetUserCustomBtnBinding.bind(includeBtn)

        setDetailsBinding.riderBtn.setOnClickListener {
            if (isCurrentTab != "rider") {
                moveIndicator(R.id.rider_btn, R.color.indi_color_green)
                loadFormLayout(R.layout.rider_details_form)
                isCurrentTab = "rider"
                viewColor(R.color.indi_color_orange)
            }
        }

        setDetailsBinding.driverBtn.setOnClickListener {
            if (isCurrentTab != "driver") {
                moveIndicator(R.id.driver_btn, R.color.indi_color_orange)
                loadFormLayout(R.layout.driver_details_form)
                isCurrentTab = "driver"
                viewColor(R.color.indi_color_green)
            }
        }

        if (savedInstanceState == null) {
        }
        moveIndicator(R.id.rider_btn, R.color.indi_color_green)
        loadFormLayout(R.layout.rider_details_form)
        isCurrentTab = "rider"
        viewColor(R.color.indi_color_orange)


    }

    private fun viewColor(colorResId: Int) {
        val color = ContextCompat.getColor(this, colorResId)
        val drawable = ContextCompat.getDrawable(this, R.drawable.circle_2)?.mutate()
        drawable?.setTint(color)
        binding.view.background = drawable
        binding.view2.background = drawable

    }

    private fun moveIndicator(targetViewId: Int, colorResId: Int) {
        val constraintSet = ConstraintSet()
        val parent = setDetailsBinding.mainSu
        constraintSet.clone(parent)

        // Animate movement
        TransitionManager.beginDelayedTransition(parent, ChangeBounds().apply {
            duration = 300
        })

        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.START,
            targetViewId,
            ConstraintSet.START
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.END,
            targetViewId,
            ConstraintSet.END
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.TOP,
            targetViewId,
            ConstraintSet.TOP
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.BOTTOM,
            targetViewId,
            ConstraintSet.BOTTOM
        )
        constraintSet.applyTo(parent)

        // --- Color animation (smooth with fade)
        val view = setDetailsBinding.indicatorView
        val toColor = ContextCompat.getColor(this, colorResId)

        val currentDrawable = view.background?.mutate() as? GradientDrawable
            ?: GradientDrawable().apply { shape = GradientDrawable.RECTANGLE }

        val fromColor = (currentDrawable.color?.defaultColor ?: toColor)

        val animator = ValueAnimator.ofObject(ArgbEvaluator(), fromColor, toColor)
        animator.duration = 300
        animator.addUpdateListener { animation ->
            val color = animation.animatedValue as Int
            currentDrawable.setColor(color)
            view.background = currentDrawable
        }
        animator.start()
    }

    private fun loadFormLayout(layoutResId: Int) {
        val inflater = LayoutInflater.from(this)
        val container = binding.formContainer
        container.removeAllViews()
        inflater.inflate(layoutResId, container, true)
    }


    /*private fun moveIndicator(targetViewId: Int, colorResId: Int) {
        val constraintSet = ConstraintSet()
        val parent = setDetailsBinding.mainSu
        constraintSet.clone(parent)

        // Set background color dynamically
        val color = ContextCompat.getColor(this, colorResId)
        val drawable = ContextCompat.getDrawable(this, R.drawable.indicator)?.mutate()
        drawable?.setTint(color)
        setDetailsBinding.indicatorView.background = drawable

        // Move indicator to center of the clicked nav
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.START,
            targetViewId,
            ConstraintSet.START
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.END,
            targetViewId,
            ConstraintSet.END
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.TOP,
            targetViewId,
            ConstraintSet.TOP
        )
        constraintSet.connect(
            R.id.indicatorView,
            ConstraintSet.BOTTOM,
            targetViewId,
            ConstraintSet.BOTTOM
        )

        TransitionManager.beginDelayedTransition(parent)
        constraintSet.applyTo(parent)
    }*/
}