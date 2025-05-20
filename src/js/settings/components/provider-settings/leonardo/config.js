const phoenixModels = [
	{
		label: "Leonardo Phoenix 1.0",
		value: "de7d3faf-762f-48e0-b3b7-9d0ac3a3fcf3",
	},
	{
		label: "Leonardo Phoenix 0.9",
		value: "6b645e3a-d64f-4341-a6d8-7a3690fbf042",
	},
];

const fluxModels = [
	{
		label: "Flux Schnell (Speed)",
		value: "1dd50843-d653-4516-a8e3-f0238ee453ff",
	},
	{
		label: "Flux Dev (Precision)",
		value: "b2614463-296c-462a-9586-aafdb8f00e36",
	},
];

const phoenixPresets = [
	{
		label: "3D Render",
		value: "debdf72a-91a4-467b-bf61-cc02bdeb69c6",
	},
	{
		label: "Bokeh",
		value: "9fdc5e8c-4d13-49b4-9ce6-5a74cbb19177",
	},
	{
		label: "Cinematic",
		value: "a5632c7c-ddbb-4e2f-ba34-8456ab3ac436",
	},
	{
		label: "Cinematic Concept",
		value: "33abbb99-03b9-4dd7-9761-ee98650b2c88",
	},
	{
		label: "Creative",
		value: "6fedbf1f-4a17-45ec-84fb-92fe524a29ef",
	},
	{
		label: "Dynamic",
		value: "111dc692-d470-4eec-b791-3475abac4c46",
	},
	{
		label: "Fashion",
		value: "594c4a08-a522-4e0e-b7ff-e4dac4b6b622",
	},
	{
		label: "Graphic Design Pop Art",
		value: "2e74ec31-f3a4-4825-b08b-2894f6d13941",
	},
	{
		label: "Graphic Design Vector",
		value: "1fbb6a68-9319-44d2-8d56-2957ca0ece6a",
	},
	{
		label: "HDR",
		value: "97c20e5c-1af6-4d42-b227-54d03d8f0727",
	},
	{
		label: "Illustration",
		value: "645e4195-f63d-4715-a3f2-3fb1e6eb8c70",
	},
	{
		label: "Macro",
		value: "30c1d34f-e3a9-479a-b56f-c018bbc9c02a",
	},
	{
		label: "Minimalist",
		value: "cadc8cd6-7838-4c99-b645-df76be8ba8d8",
	},
	{
		label: "Moody",
		value: "621e1c9a-6319-4bee-a12d-ae40659162fa",
	},
	{
		label: "None",
		value: "556c1ee5-ec38-42e8-955a-1e82dad0ffa1",
	},
	{
		label: "Portrait",
		value: "8e2bc543-6ee2-45f9-bcd9-594b6ce84dcd",
	},
	{
		label: "Pro B&W photography",
		value: "22a9a7d2-2166-4d86-80ff-22e2643adbcf",
	},
	{
		label: "Pro color photography",
		value: "7c3f932b-a572-47cb-9b9b-f20211e63b5b",
	},
	{
		label: "Pro film photography",
		value: "581ba6d6-5aac-4492-bebe-54c424a0d46e",
	},
	{
		label: "Portrait Fashion",
		value: "0d34f8e1-46d4-428f-8ddd-4b11811fa7c9",
	},
	{
		label: "Ray Traced",
		value: "b504f83c-3326-4947-82e1-7fe9e839ec0f",
	},
	{
		label: "Sketch (B&W)",
		value: "be8c6b58-739c-4d44-b9c1-b032ed308b61",
	},
	{
		label: "Sketch (Color)",
		value: "093accc3-7633-4ffd-82da-d34000dfc0d6",
	},
	{
		label: "Stock Photo",
		value: "5bdc3f2a-1be6-4d1c-8e77-992a30824a2c",
	},
	{
		label: "Vibrant",
		value: "dee282d3-891f-4f73-ba02-7f8131e5541b",
	},
];

const fluxPresets = [
	{
		label: "3D Render",
		value: "debdf72a-91a4-467b-bf61-cc02bdeb69c6",
	},
	{
		label: "Acrylic",
		value: "3cbb655a-7ca4-463f-b697-8a03ad67327c",
	},
	{
		label: "Anime General",
		value: "b2a54a51-230b-4d4f-ad4e-8409bf58645f",
	},
	{
		label: "Creative",
		value: "6fedbf1f-4a17-45ec-84fb-92fe524a29ef",
	},
	{
		label: "Dynamic",
		value: "111dc692-d470-4eec-b791-3475abac4c46",
	},
	{
		label: "Fashion",
		value: "594c4a08-a522-4e0e-b7ff-e4dac4b6b622",
	},
	{
		label: "Game Concept",
		value: "09d2b5b5-d7c5-4c02-905d-9f84051640f4",
	},
	{
		label: "Graphic Design 3D",
		value: "7d7c2bc5-4b12-4ac3-81a9-630057e9e89f",
	},
	{
		label: "Illustration",
		value: "645e4195-f63d-4715-a3f2-3fb1e6eb8c70",
	},
	{
		label: "None",
		value: "556c1ee5-ec38-42e8-955a-1e82dad0ffa1",
	},
	{
		label: "Portrait",
		value: "8e2bc543-6ee2-45f9-bcd9-594b6ce84dcd",
	},
	{
		label: "Portrait Cinematic",
		value: "4edb03c9-8a26-4041-9d01-f85b5d4abd71",
	},
	{
		label: "Ray Traced",
		value: "b504f83c-3326-4947-82e1-7fe9e839ec0f",
	},
	{
		label: "Stock Photo",
		value: "5bdc3f2a-1be6-4d1c-8e77-992a30824a2c",
	},
	{
		label: "Watercolor",
		value: "1db308ce-c7ad-4d10-96fd-592fa6b75cc4",
	},
];

const phoenixConfig = {
	presets: phoenixPresets,
};

const fluxConfig = {
	presets: fluxPresets,
}

export const config = {
	"de7d3faf-762f-48e0-b3b7-9d0ac3a3fcf3": phoenixConfig,
	"6b645e3a-d64f-4341-a6d8-7a3690fbf042": phoenixConfig,
	"1dd50843-d653-4516-a8e3-f0238ee453ff": fluxConfig,
	"b2614463-296c-462a-9586-aafdb8f00e36": fluxConfig,
	models: [
		...phoenixModels,
		...fluxModels,
	]
};
