# amember-makerspace-forms

[![Build Status](https://ci.t2l.io/job/ENTS%20-%20aMember%20Pro/job/amember-makerspace-forms/badge/icon)](https://ci.t2l.io/job/ENTS%20-%20aMember%20Pro/job/amember-makerspace-forms/)

aMember Pro plugin that adds additional forms for members/admins targeted towards Makerspaces

# Installation

1. Download the latest build from [Jenkins](https://ci.t2l.io/job/ENTS%20-%20aMember%20Pro/job/amember-makerspace-forms/)
2. Extract the `amember` folder to your aMember Pro root directory
3. Enable the plugin within aMember Pro (under 'Other Plugins')

# Optional Fields

If the plugin finds a user field listed below has been created then it will show a field for it on applicable forms (like the 'Add Member' form).

* `fob` - An SQL text field
* `id_type` - An SQL text field
* `waiver_signed` - An SQL text field displayed as a checkbox (with no options)

The suggested configuration for each field is recorded in [FIELDS.md](FIELDS.md).
