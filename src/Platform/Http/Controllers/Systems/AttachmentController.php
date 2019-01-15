<?php

declare(strict_types=1);

namespace Orchid\Platform\Http\Controllers\Systems;

use Orchid\Attachment\File;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Platform\Http\Controllers\Controller;

/**
 * Class AttachmentController.
 */
class AttachmentController extends Controller
{
    /**
     * AttachmentController constructor.
     */
    public function __construct()
    {
        $this->checkPermission('platform.systems.attachment');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $attachment = [];
        foreach ($request->allFiles() as $files) {

            $files = array_wrap($files);

            foreach ($files as $file) {
                $attachment[] = $this->createModel($file, $request);
            }
        }

        if (count($attachment) > 1) {
            return response()->json($attachment);
        }

        return response()->json(reset($attachment));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function sort(Request $request)
    {
        collect($request->get('files'))
            ->each(function ($sort, $id) {
                $attachment = Attachment::find($id);
                $attachment->sort = $sort;
                $attachment->save();
            });

        return response(200);
    }

    /**
     * Delete files.
     *
     * @param         $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function destroy($id, Request $request)
    {
        $storage = $request->get('storage', 'public');
        Attachment::find($id)->delete($storage);

        return response(200);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilesByIds(Request $request)
    {
        $files = Attachment::whereIn('id', $request->get('files'))
            ->oldest('sort')
            ->get();

        return response()->json($files);
    }

    /**
     * @param         $id
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        Attachment::findOrFail($id)
            ->fill($request->all())
            ->save();

        return response(200);
    }

    /**
     * @param $file
     * @param Request $request
     * @return mixed
     */
    private function createModel($file, Request $request)
    {
        $model = app()->make(File::class, [
            'file'  => $file,
            'disk'  => $request->get('storage', 'public'),
            'group' => $request->get('group'),
        ])->load();

        $model->url = $model->url();

        return $model;
    }
}
