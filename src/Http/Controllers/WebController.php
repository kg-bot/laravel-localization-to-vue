<?php


namespace KgBot\LaravelLocalization\Http\Controllers;


use Illuminate\Http\Request;
use KgBot\LaravelLocalization\Facades\ExportLocalizations;
use KgBot\LaravelLocalization\Rules\LocaleAlreadyExist;
use Illuminate\Filesystem\Filesystem;

class WebController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $languages = ExportLocalizations::getLocales();
        $groups = ExportLocalizations::getGroups();


        return view('laravel-localization::index', compact('languages', 'groups'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addNewLocale(Request $request)
    {
        $this->validate($request, [

            'locale' => [

                'string',
                new LocaleAlreadyExist()
            ]
        ]);

        ExportLocalizations::createNewLocale($request->get('locale'));

        return back()->with('success', __('New locale created.'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteLocale(Request $request)
    {
        ExportLocalizations::deleteLocales($request->input('remove-locale'));

        return back()->with('success', __('Locale deleted') . '!');
    }

    public function openGroup($group)
    {
        $groups = ExportLocalizations::getGroups();

        if(in_array($group, array_values($groups))) {
            $languages = ExportLocalizations::getLocales();

            return view('laravel-localization::group', [
                'group_name' => $group,
                'languages' => array_values($languages),
                'group' => ExportLocalizations::getGroup($group),
                'groups' => $groups
            ]);
        }

        return redirect()->back()->with('error', __('Selected group does not exist.'));
    }

    public function postKey(Request $request)
    {
        $group_name = explode('/', $request->get('name'))[0];
        $key = explode('/', $request->get('name'))[1];

        ExportLocalizations::writeValue($group_name, $key, $request->get('pk'), $request->get('value'));

        return response()->json('success');
    }

    public function addKeys(Request $request)
    {
        $groups = ExportLocalizations::getGroups();

        if(in_array($request->get('group'), array_values($groups))) {

            $group_name = $request->get('group');
            $keys = explode("\n", $request->get('keys'));

            ExportLocalizations::writeKeys($group_name, $keys);

            return redirect()->route('laravel-localization.get-open-group', $group_name);


        }

        return redirect()->back()->with('error', __('Selected group does not exist.'));
    }

    public function postGroup(Request $request)
    {
        $groups = ExportLocalizations::getGroups();

        if(!in_array($request->get('new-group'), array_values($groups))) {

            ExportLocalizations::createNewGroup($request->get('new-group'));

            return redirect()->route('laravel-localization.get-open-group', $request->get('new-group'));
        }

        return redirect()->back()->with('error', __('This group already exists.'));
    }

}