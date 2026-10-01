/* code_block-209-1688 */
document.addEventListener("DOMContentLoaded", (heroSlider) => {
const sections = gsap.utils.toArray(".slide");
const outerWrappers = gsap.utils.toArray(".slide__outer");
const innerWrappers = gsap.utils.toArray(".slide__inner");
const count = document.querySelector(".slide-count");
const wrap = gsap.utils.wrap(0, sections.length);
let animating;
let currentIndex = 0;

gsap.set(outerWrappers, { xPercent: 100 });
gsap.set(innerWrappers, { xPercent: -100 });
gsap.set(".slide:nth-of-type(1) .slide__outer", { xPercent: 0 });
gsap.set(".slide:nth-of-type(1) .slide__inner", { xPercent: 0 });
gsap.set(".slide-count-total", {text: sections.length});

var firstSlide = document.querySelector('.slides-repeater .slide');

// Check if the first slide is found
if (firstSlide) {
	// Change the z-index of the first slide
	firstSlide.style.zIndex = '2'; // Set your desired z-index value here
}

let timer;
const runTimer = () => {
	timer = window.setInterval(() => {
		gotoSection(currentIndex + 1, 1);
	}, 7500); // temps entre chaque slide
};

runTimer();

function gotoSection(index, direction) {
	animating = true;
	index = wrap(index);

	let tl = gsap.timeline({
		defaults: { duration: 1, ease: "Power2.inOut" },
		onComplete: () => {
			animating = false;
		}
	});

	let currentSection = sections[currentIndex];
	let nextSection = sections[index];

	gsap.set([sections[currentIndex]], { zIndex: 1, autoAlpha: 1 });
	gsap.set([sections[index]], { zIndex: 2, autoAlpha: 1 });
	

	tl
		.set(count, { text: index + 1 }, 0.32)
		.fromTo(
		outerWrappers[index],
		{
			xPercent: 100 * direction
		},
		{ xPercent: 0 },
		0
	)
		.fromTo(
		innerWrappers[index],
		{
			//scale: .8,
			xPercent: -100 * direction
		},
		{ 
			//scale: 1,
			xPercent: 0
		},
		0
	)

	//.timeScale(0.8);

	currentIndex = index;
}
	});
